<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;


class CB_Admin_Form {
  public $html;

  public function __construct($type = NULL){
    $this->set_html();
  }

  private function set_html(){
    $content = '<header class="modal-header"><h2 class="woocommerce-order-data__heading">Add Booking</h2> ';
    $content .= '<span class="cb-booking-subtitle">Create an order, add a booking</span>';

    $content .= '<button class="modal-close-link cb-close-booking dashicons dashicons-no-alt">
              </button></header>';
    $content .= '<div class="cb-booking-container cb-admin-form-addbooking" >';
    $content .= $this->set_booking_form();
    $content .= '</div>';
    $this->html = $content;
  }

  private function set_booking_form(){
    $content = '<form>';
    $content .= '<section class="pattern" id="user">';
    $content .= '<h2>Customer</h2>';
    $content .= '<p><input type="text" id="first_name" placeholder="first name" class="regular-text" required/><br>';
    $content .= '<input type="text" id="last_name" placeholder="last name" class="regular-text" required/><br>';
    $content .= '<input type="text" id="email" placeholder="email" class="regular-text" required/><br>';
    $content .= '<input type="text" id="phone" placeholder="phone" class="regular-text" required/></p>';
    $content .= '<h3>Billing Address</h3><input type="text" id="address_1" placeholder="address " class="regular-text" required/><br>';
    $content .= '<input type="text" id="address_2" placeholder="address 2" class="regular-text" /><br>';
    $content .= '<input type="text" id="city" placeholder="city " class="regular-text" required/><br>';
    $content .= '<input type="text" id="state" placeholder="state " class="regular-text" required/><br>';
    $content .= '<input type="text" id="postcode" placeholder="postcode" class="regular-text" required/><br>';
    $content .= '<input type="text" id="country" placeholder="country" class="regular-text" required/>
    </p><hr>';
    $content .= '</section>';
    $content .= $this->set_calendar();
    $content .= '</form>';
    return $content;
  }

  private function set_calendar(){
    $date = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $products_listing = new CB_List_Products($date->format('Y-m-d'), 'yes', 'yes', 'toorder');
    $content = '<section class="pattern" id="cb-availability">';
    $content .= '<h2>Availability</h2>';
    $content .= '<div class="cb-lazyload-global-calendar" date="'.$date->format('Y-m-d').'" list_action="toorder"><div class="cb-hold-calendar-loader"><span class="fa-stack fa-lg">
    <i class="fa fa-circle fa-stack-2x"></i>
    <i class="fa fa-compass fa-inverse fa-spin fa-stack-2x"></i>
  </span></div></div>';
    $content .= '<div>'.$products_listing->html.'</div>';
    $content .= '</section>';
    return $content;
  }


} //end class declaration

 ?>
