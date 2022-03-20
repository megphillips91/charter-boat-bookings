<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

add_action( 'wp_ajax_nopriv_cb_summary', __NAMESPACE__ . '\\cb_summary_callback' );
add_action( 'wp_ajax_cb_summary', __NAMESPACE__ . '\\cb_summary_callback' );

function cb_summary_callback(){
	$product_id = sanitize_text_field($_POST['product_id']);
	$calendar = new CB_Product_Calendar('product', NULL, NULL, $product_id);
	$product_listing = new CB_List_Product($product_id, NULL, 'yes');
	$response['product_listing'] = $product_listing;
	$response['calendar'] = $calendar;
	$content = '<div class="cb-hold-summary"><div class="cb-wrap-global-display cb-entry-summary" id="'.rand().'"><div class="hold-product-calendar cb-calendar-container" id="'.rand().'">'.$calendar->html.'</div>';
	$content .= '<div class="hold-product-listing" id="'.rand().'">'.$product_listing->html.'</div></div>';
	$response['html'] = $content;
	wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_book_now_tab', __NAMESPACE__ . '\\cb_book_now_tab_callback' );
add_action( 'wp_ajax_cb_book_now_tab', __NAMESPACE__ . '\\cb_book_now_tab_callback' );

function cb_book_now_tab_callback(){
	$response = array();
	$product_id = sanitize_text_field($_POST['product_id']);
	$content = '<div class="cb-single-book-now-tab" product_id="'.$product_id.'" id="'.rand().'">';
	$calendar = new CB_Product_Calendar('product', NULL, NULL, $product_id);
	$product_listing = new CB_List_Product($product_id, NULL, 'yes');
	$content = '<div class="cb-single-book-now-tab" product_id="'.$product_id.'">';
	$content .= '<div class="cb-wrap-global-display">';
  $content .= $calendar->html;
	$content .= '<div class="hold-product-listing">'.$product_listing->html;
	$content .= '</div></div>';
	$content .= '</div>';
	$response['html'] = $content;
	wp_send_json($response);
}


/**
 * Single Booking Page Template Hooks
 *
 * Single Product Page template hooks for how the bookings single product pages display_booking_details
 * -- shows calendar
 */
//display the charter availability calendar

function cb_product_page() {
	if(is_charter_booking(get_the_ID())){
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
		remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
		$product_id = get_the_ID();
		$content = '<div class="cb-entry-summary lazyload-cb-summary cb-loading" id="'.rand().'" product_id="'.$product_id.'">';
		$content .= '<h3><span class="fa-stack fa-lg">
  <i class="fa fa-circle fa-stack-2x"></i>
  <i class="fa fa-compass fa-inverse fa-spin fa-stack-2x"></i>
</span></h3><h3>searching availabilty</h3>';
		$content .= '</div>';
		echo $content;
	}
}
//add_action( 'woocommerce_single_product_summary' , __NAMESPACE__ . '\\cb_product_page', 5 );
add_action( 'woocommerce_after_single_product_summary' , __NAMESPACE__ . '\\cb_product_page', 5 );

/**
 * Add Book Now Tab to Charter Booking Single Product pages
 *
 * Add custom tab to woocommerce single product page which displays calendar and product listing under a "book now" tab
 *
 */
function cb_book_now_product_tab( $tabs ) {
  global $product;
  $product_id = $product->get_ID();
  if(is_charter_booking($product_id)){
   $tabs['cb_book_now'] = array(
     'title'    => __( 'Book Now', 'textdomain' ),
     'callback' => __NAMESPACE__ . '\\cb_book_now_tab_content',
     'priority' => 10,
   );
   return $tabs;
  }
}
add_filter( 'woocommerce_product_tabs', __NAMESPACE__ . '\\cb_book_now_product_tab' );

function cb_book_now_tab_content(){
  global $product;
  $product_id = $product->get_ID();
	$content = '<div class="cb-single-book-now-tab cb-loading " product_id="'.$product_id.'" >';
	$content .= '<h3><span class="fa-stack fa-lg">
	<i class="fa fa-circle fa-stack-2x"></i>
	<i class="fa fa-compass fa-inverse fa-spin fa-stack-2x"></i>
</span> searching availabilty</h3>';
	$content .= '</div>';
  echo $content;
}


/**
 * Change the "description" tab label to be Details instead
 * @var [type]
 */
add_filter( 'woocommerce_product_description_tab_title', __NAMESPACE__ . '\\cb_rename_description_product_tab_label' );

function cb_rename_description_product_tab_label() {
	global $product;
  $product_id = $product->get_ID();
	if(is_charter_booking($product_id)){
		return 'Details';
	} else {
		return 'Description';
	}
}

/**
 * Remove standard Woocommerce formatted price from CB Product page
 *
 */
add_filter('woocommerce_get_price_html', __NAMESPACE__ . '\\cb_move_price');
function cb_move_price($price){
  $product_id = \get_the_ID();
	if(is_charter_booking($product_id) && is_single()){
		return '';
	} else {
		return $price;
	}
}



 ?>
