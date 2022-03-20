<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;
/**
 * ========================================
 * functions which service the charter booking product type on the Admin 'add product' screen.
 * =========================================
 */

//adds charter booking type into product type drop down
 function custom_product_type_to_type_selector( $types ){
     $types[ 'charter_booking' ] = __( 'Charter Booking', 'charter_booking' );
     return $types;
 }
 add_filter( 'product_type_selector', __NAMESPACE__ . '\\custom_product_type_to_type_selector' );

//hides shipping panel when product type is charter booking
function hide_attributes_data_panel( $tabs) {
	$tabs['shipping']['class'][] = 'hide_if_charter_booking';
	$tabs['attribute']['class'][] = 'hide_if_charter_booking';
  $tabs['inventory']['class'][] = 'hide_if_charter_booking';

	return $tabs;
}
add_filter( 'woocommerce_product_data_tabs', __NAMESPACE__ . '\\hide_attributes_data_panel' );


// adds the duration, location, start_time to the general product settings tab
function wc_add_charter_bookings_fields() {

  echo '<div class="options_group show_if_charter_booking ">';

  woocommerce_wp_text_input( array(
    'id' => '_cb_reservation_fee',
    'label' => sanitize_text_field('Reservation Fee ($)'),
    'description' => 'Reservation Fee required to reserve the charter.',
		'desc_tip' => 'true',
    'placeholder' => 'reservation fee'
    )
  );

	woocommerce_wp_text_input( array(
    'id' => '_cb_final_balance',
    'label' => sanitize_text_field('Final Balance ($)'),
    'description' => 'Guests prompted to pay the final balance 3 days prior to charter.',
		 'desc_tip' => 'true',
    'placeholder' => 'final balance'
    )
  );

	echo '</div> <div class="options_group show_if_charter_booking">';

	$nicknames = cb_get_location_options();
	$nicknames['']='';
  woocommerce_wp_select( array(
    'id' => '_cb_location',
    'label' => 'Location',
    'description' => 'Which location is this charter departing?',
    'desc_tip' => 'true',
    'placeholder' => 'Location',
    'options'=>$nicknames
    )
  );

	woocommerce_wp_checkbox( array(
    'id' => '_cb_is_sunset',
    'label' => 'Sunset Cruise?',
    'description' => 'If checked, the sunset API will be enabled. The start time below is ignored',
    'desc_tip' => 'true'
    )
  );

  woocommerce_wp_text_input( array(
    'id' => '_cb_start_time',
    'label' => 'Charter Start Time',
    'type'=>'time',
    'description' => 'Boarding time for guests',
    'desc_tip' => 'true',
		'default'=>'',
		'class'=>'timepicker'
    )
  );

$options = 	cb_pipe_array(get_option('cb_durations'));
$options['']='';
  woocommerce_wp_select( array(
    'id' => '_cb_duration',
    'label' => 'Charter Duration (h)',
    'description' => 'Length of the charter',
    'desc_tip' => 'true',
    'options'=>$options
    )
  );
  echo '</div>';
} // end add input fields
add_action('woocommerce_product_options_general_product_data', __NAMESPACE__ . '\\wc_add_charter_bookings_fields' );


/**
 * Show pricing fields for charter_booking product type.
 */
function charter_booking_custom_js() {

	if ( 'product' != get_post_type() ) :
		return;
	endif;

	?><script type='text/javascript'>
		jQuery( document ).ready( function() {
			jQuery( '.options_group.pricing' ).addClass( 'show_if_charter_booking' ).show();
		});
	</script><?php
}
add_action( 'admin_footer', __NAMESPACE__ . '\\charter_booking_custom_js' );

//saves custom meta on post update
function wc_custom_charter_booking_fields( $post_id ) {
  if(session_status() === PHP_SESSION_NONE){session_start();}
  if(is_charter_booking($post_id)){
      $_SESSION['cb_fields'] = 'complete';
    }
  $fields = array(
    '_cb_duration',
    '_cb_start_time',
    '_cb_location',
    '_cb_reservation_fee',
		'_cb_final_balance'
  );
  //loop through fields and update meta
  foreach ($fields as $key=>$field) {
    if ( ! empty( $_POST[$field] ) ) {
        update_post_meta(
          $post_id,
          $field,
          sanitize_text_field( $_POST[$field] ) );
    } else {
      if(is_charter_booking($post_id)){
			     $_SESSION['cb_fields'] = 'empty';
      }
		}
  }

  //pertains to sunset time
	if(!empty($_POST['_cb_is_sunset'])){
			update_post_meta(
				$post_id,
				'_cb_is_sunset',
				sanitize_text_field( $_POST['_cb_is_sunset'] ) );
		} else {
			update_post_meta(
				$post_id,
				'_cb_is_sunset',
				sanitize_text_field( 'no' ) );
		}

  if(is_charter_booking($post_id)){
    update_post_meta($post_id, '_sold_individually', 'yes');
     //update changes to reservation fee within all variations
	   $variations = cb_get_variations($post_id);
	   foreach($variations['reservation_posts'] as $reservation){
       update_post_meta(
         $reservation,
         '_regular_price',
         sanitize_text_field( $_POST['_cb_reservation_fee']) );
     }
     //update changes to final balance fee
     foreach($variations['finalbalance_posts'] as $finalbalance){
       update_post_meta(
         $finalbalance,
         '_regular_price',
         sanitize_text_field( $_POST['_cb_final_balance']) );
     }
     //set end time product meta
   }

}
add_action( 'woocommerce_process_product_meta', __NAMESPACE__ . '\\wc_custom_charter_booking_fields' );



function cb_product_fields_notice() {
  if(session_status() === PHP_SESSION_NONE && !headers_sent()){session_start();}
  	if(isset($_SESSION['cb_fields']) && $_SESSION['cb_fields'] == 'empty'){
    $notice  = '
      <div class="notice notice-error is-dismissible">
          <p>'.'All charter booking fields are required! Please complete all fields. Otherwise, the booking calendar will not function properly.'.'</p>
      </div>';
  		echo $notice;
  	}
}
add_action( 'admin_notices', __NAMESPACE__ . '\\cb_product_fields_notice' );

/**
 * Filter Sold Individually
 *
 * filter to true for every charter booking product in this lite version.
 *
 */
function cb_sold_individually( $individually, $product ){
  if(is_charter_booking($product->get_id())){
    $individually = true;
  }
  return $individually;
}
add_filter( 'woocommerce_is_sold_individually', __NAMESPACE__ . '\\cb_sold_individually', 10, 2 );



 ?>
