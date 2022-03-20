<?php

/**
 * Charter Bookings product class
 *
 *  Adds custom product type - chartr bookings
 *  Helper functions that interface with the custom product type
 *
 */


/**
 * Charter Booking Product Class
 *
 * creates the charter booking product type in WC admin add product page
 *
 */
function register_charter_boat_booking_product_type() {
/**
 * Charter Booking Product Class
 *
 */
	class WC_Product_Charter_Booking extends WC_Product {

		public function __construct( $product ) {
			$this->product_type = 'charter_booking';
			$this->set_virtual(true);
      $this->set_charter_booking_product_meta();
			parent::__construct( $product );

		}
		/*
		** getters for charter booking object
		*/

    //save custom product_type
    public function get_type() {
        return 'charter_booking';
    }

    public function get_booking_meta(){
      return $this->get_meta('cb_booking_meta');
    }

    /*
    * setters for charter booking object
     */
    //set custom meta data into product meta object
    public function set_charter_booking_product_meta(){
      $booking_meta = array();
      $booking_meta['cb_start_time']=get_post_meta(get_the_ID(), '_cb_start_time', true);
      $booking_meta['cb_location']=get_post_meta(get_the_ID(), '_cb_location', true);
      $booking_meta['cb_duration']=get_post_meta(get_the_ID(), '_cb_duration', true);
      $booking_meta['cb_reservation_fee']=get_post_meta(get_the_ID(), '_cb_reservation_fee', true);
      $booking_meta['cb_final_balance']=get_post_meta(get_the_ID(), '_cb_final_balance', true);
      $this->add_meta_data('cb_booking_meta', $booking_meta, true);
    }

	}//end class declaration

}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if(is_plugin_active('woocommerce/woocommerce.php')){
add_action( 'init', 'register_charter_boat_booking_product_type' );
}

 ?>
