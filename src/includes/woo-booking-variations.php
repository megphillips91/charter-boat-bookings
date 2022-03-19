<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;
use \WC_Product_Variation;

/**
 * Charter Booking Product variations
 *
 * A key feature of this plugin is that it supports a charter reservation fee as a separate product variation from the charter final balance. The idea is that the reservation could be non-refundable and/or deliver reservation funds to a charter agent on a separate business transaction from the total charter fee and or final balance payment therein not co-mingling funds of the booking agent and the charter provider.
 *
 * Furthermore, being weather dependant and often a bit volatile, the larger portion of funds is not prompted to be paid until 3 days prior to the charter boarding date/time. This 3 day window cooresponds to the approxiamate time that it takes a cc transation to pay out to bank so that refunds are much easier for the business to handle.
 *
 * Handles the charter reservation and charter fee as a product variation of the parent charter booking product.
 *
 * The booking call should check an option in wp_options as to whether reservations are enabled or not. if not, then charge full product cost up front vs. reservation nd then charterfee
 *
 * But, for now this just assumes that the option is set to yes for reservations so it creates a reservation and a confirmation variation as theses products are requested to be added to the cart by the user.
 *
 * The charter confirmation variation is only created once the user clicks to pay for the final balance of the charter.
 *
 *
 */

/**
 * create array of all variations
 * @param int $product_id
 * @return array
 */
function cb_get_variations($product_id){
  global $wpdb;
  $qry = "select distinct(ID) from wp_posts where post_name LIKE 'product-'".$product_id."'-reservation%'";
  $results = $wpdb->get_results($qry);
  $reservation_posts = cb_wp_collapse($results, 'ID');
  $qry = "select distinct(ID) from wp_posts where post_name LIKE 'product-'".$product_id."'-finalbalance%'";
  $results = $wpdb->get_results($qry);
  $finalbalance_posts = cb_wp_collapse($results, 'ID');
  $qry = "select distinct(ID) from wp_posts where post_name LIKE 'product-'".$product_id."'-fullcharter%'";
  $results = $wpdb->get_results($qry);
  $fullcharter_posts = cb_wp_collapse($results, 'ID');
  return array('reservation_posts'=>$reservation_posts,
    'finalbalance_posts'=>$finalbalance_posts,
    'fullcharter'=>$fullcharter_posts
  );
}

/**
 * CB Variation Exists?
 * checks if variation exists, returns ID if yes, returns false if no.
 *
 * @param  int $product_id [description]
 * @param  string $date       Y-m-d
 * @param  string $type       reservation or confirmation or full charter?
 * @return int  returns wc variation id on true, false on false
 */
function cb_variations_exist($product_id, $date){
   $response = array();
   $product = wc_get_product($product_id);
   global $wpdb;
   $qry = "select * from wp_posts a
      where post_name LIKE '%".$product_id."%'
      AND post_name LIKE '%".$date."%'";
    $variations = $wpdb->get_results($qry);
    if($variations) {return $variations;} else {return false;}
 }

 /**
 * Checks if booking date variation already exists
 * If so, return ID
 * If not, create it and return ID
 * @param int $product_id | product id of the parent reservation product
 * @param array $variation_data | The data to insert in the product.
 * @return array $variation_id
 **/

 function cb_variation($product_id, $date, $type){
  $response = array();
	//check if variation exists
  $args = array(
 	 'name' => 'product-'.$product_id.'-'.$type.'-'.$date,
 	 'post_status'=>'publish',
 	 'post_type'=>'product_variation'
  );
  $variation = \get_posts($args);
	  if($variation) {
			return $variation[0]->ID ;
		} else {
			$variation_data = cb_set_variation_data($product_id, $date, $type);
			$variation_id = create_product_variation( $product_id, $variation_data);
			return $variation_id;
		}
 }

 /**
  * Sets variation data for create_product_variation
  * @param  int $product_id of the charter booking product
  * @param  string $date  of the charter
  * @param  string $type  either reservation or charterfee
  * @return array $variation_data
  */

  function cb_set_variation_data($product_id, $date, $type){
		global $woocommerce;
 	  $product = \wc_get_product($product_id);
    $stock_qty = 1;
    $booking_meta = get_charter_booking_meta($product_id);

    $regular_price = $product->get_regular_price();
    $balanceduedate = calc_balance_due_date($date);
		$regular_price = ($type == 'reservation')
			? $booking_meta['_cb_reservation_fee']
			: $booking_meta['_cb_final_balance'] ;
    switch ($type) {
      case 'reservation':
        $regular_price = $booking_meta['_cb_reservation_fee'];
        $balance_due = $booking_meta['_cb_final_balance'];
        break;
      case 'finalbalance':
        $regular_price = $booking_meta['_cb_final_balance'];
        $balance_due = $booking_meta['_cb_final_balance'];
        break;
      case 'fullcharter':
        $regular_price = $product->get_regular_price();
        $balance_due = '0';
    }

    // The variation data
    $variation_data =  array(
        'attributes' => array(
						'_cb_type'=>$type,
            '_cb_date'  => $date,
            '_cb_duration'=>$booking_meta['_cb_duration'],
            '_cb_start_time'=>$booking_meta['_cb_start_time'],
            '_cb_location'=>$booking_meta['_cb_location'],
            '_cb_balance_due_date'=>$balanceduedate,
            '_cb_balance_due'=> $balance_due
        ),
        'sku'           => '',
        'regular_price' => $regular_price,
        'sale_price'    => '',
        'stock_qty'     => $stock_qty,
 			 'post_name' => 'product-'.$product_id.'-'.$type.'-'.$date,
 			 'post_title'=>$product->get_title().' '.$type
    );
		if($booking_meta['_cb_is_sunset'] == 'yes'){
	    $sunset = new CB_Sunset_Time($booking_meta['_cb_location'], $date);
	    $chartertimes = $sunset->get_charter_start_time($booking_meta['_cb_duration']);
	    $variation_data['attributes']['_cb_start_time'] = $chartertimes['boarding'];
		}
 	 return $variation_data;
  }


/**
 * Create a product variation to handle the booking date given the reservation obje product ID.
 *
 * @param int   $product_id | Post ID of the product parent variable product.
 * @param array $variation_data | The data to insert in the product.
 */

function create_product_variation( $product_id, $variation_data ){
    // Get the Variable product object (parent)
    $product = wc_get_product($product_id);
    $variation_post = array(
        'post_title'  => $variation_data['post_title'],
        'post_name'   => $variation_data['post_name'],
        'post_status' => 'publish',
        'post_parent' => $product_id,
        'post_type'   => 'product_variation',
        'guid'        => $product->get_permalink()
    );

    // Creating the product variation
    $variation_id = wp_insert_post( $variation_post );

    // Get an instance of the WC_Product_Variation object
    $variation = new WC_Product_Variation( $variation_id );

    // Iterating through the variations attributes
    foreach ($variation_data['attributes'] as $attribute => $term_name )
    {
        $taxonomy = 'pa_'.$attribute; // The attribute taxonomy

        // If taxonomy doesn't exists we create it (Thanks to Carl F. Corneil)
        if( ! taxonomy_exists( $taxonomy ) ){
            register_taxonomy(
                $taxonomy,
               'product_variation');
        }

        // Check if the Term name exist and if not we create it.
        if( ! term_exists( $term_name, $taxonomy ) )
            wp_insert_term( $term_name, $taxonomy ); // Create the term

        $term_slug = get_term_by('name', $term_name, $taxonomy )->slug; // Get the term slug

        // Get the post Terms names from the parent variable product.
        $post_term_names =  wp_get_post_terms( $product_id, $taxonomy, array('fields' => 'names') );

        // Check if the post term exist and if not we set it in the parent variable product.
        if( ! in_array( $term_name, $post_term_names ) )
            wp_set_post_terms( $product_id, $term_name, $taxonomy, true );

        // Set/save the attribute data in the product variation
        update_post_meta( $variation_id, 'attribute_'.$taxonomy, $term_slug );
    }

    ## Set/save all other data

    // SKU
    if( ! empty( $variation_data['sku'] ) )
        $variation->set_sku( $variation_data['sku'] );

    // Prices
    if( empty( $variation_data['sale_price'] ) ){
        $variation->set_price( $variation_data['regular_price'] );
    } else {
        $variation->set_price( $variation_data['sale_price'] );
        $variation->set_sale_price( $variation_data['sale_price'] );
    }
    $variation->set_regular_price( $variation_data['regular_price'] );
    $variation->set_sold_individually('yes');
    $variation->set_virtual('yes');

    // Stock
    if( ! empty($variation_data['stock_qty']) ){
        $variation->set_stock_quantity( $variation_data['stock_qty'] );
        $variation->set_manage_stock(true);
        $variation->set_stock_status('instock');
    } else {
        $variation->set_manage_stock(false);
    }

    $variation->set_weight(''); // weight (reseting)

    $variation->save(); // Save the data

		return $variation_id;
}

 ?>
