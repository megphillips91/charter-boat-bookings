<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

//add_shortcode('cb_send_reminder', 'cb_send_reminder_shortcode');
function cb_send_reminder_shortcode($atts){
  $sunsettime = new CB_Sunset_Time('Avon', '2019-11-2019');
  echo '<pre>'; var_dump($sunsettime); echo '</pre>';
}

//add_shortcode('cb_test_emails', __NAMESPACE__ . '\\test_charter_emails');
function test_charter_emails(){
$message = new CB_Admin_Charter_Schedule();
echo '<pre>'; var_dump($message); echo '</pre>';
//$message->send();
}


add_shortcode('cb_show_product', __NAMESPACE__ . '\\cb_show_product_shortcode');
function cb_show_product_shortcode($atts){
  $atts = shortcode_atts(
		array(
      'product_id' => NULL,
      'date'=>NULL
		), $atts, 'cb_show_product' );
  $product_listing = new CB_List_Product($atts['product_id'], $atts['date']);
  //echo '<pre>'; var_dump($product_listing); echo '<pre>';
  return $product_listing->html;
}

add_shortcode('cb_global_calendar', __NAMESPACE__ . '\\cb_calendar_shortcode');
function cb_calendar_shortcode($atts){
  $atts = shortcode_atts(
		array(
      'date'=>NULL
		), $atts, 'cb_global_calendar' );
  $content = '<div class="cb-wrap-global-display">';
  //$calendar = new CB_Global_Calendar('global', 11, 2019, NULL);
  //$content .= $calendar->html;
  $listing = new CB_List_Products('2019-10-27');
  //echo'<pre>';var_dump($listing); echo '</pre>';
  $content .= $listing->html;
  $content .= '</div>';
  return $content;
}

add_shortcode('cb_product_calendar', __NAMESPACE__ . '\\cb_product_calendar_shortcode');
function cb_product_calendar_shortcode($atts){
  $calendar = new CB_Product_Calendar(NULL, NULL, NULL, 1034);
  return $calendar->html;
}


//add_shortcode('sitewide_availability', 'cb_sitewide_availability_shortcode');
function cb_sitewide_availability_shortcode($atts){
  //set up defaults
  $atts = shortcode_atts(
		array(
      'product_id' => 1098,
      'date'=>'2019-12-25'
		), $atts, 'sitewide_availability' );
  //instantiate availability
  $availability = new CB_Product_Availability($atts['date'], $atts['product_id']);
  echo '<pre>';var_dump($availability);echo '</pre>';
  return $availability->available;
}

//add_shortcode('testing_sql_prepare', __NAMESPACE__ . '\\cb_prepare_sql');
function cb_prepare_sql($atts){
  $atts = shortcode_atts(
		array(
      'charter_date'=>'2022-03-18'
		), $atts, 'testing_sql_prepare' );
    $charters = new CB_Charters('2022-03-01', '2022-05-31');
    ob_start();
    echo '<pre>';
    var_dump($charters);
    echo '</pre>';
    return ob_get_clean();
}



add_shortcode('charter_booking_confirmation', __NAMESPACE__ . '\\charter_booking_confirmation_shortcode');

function charter_booking_confirmation_shortcode () {
  if(!isset($_GET['booking_id'])){
    $content = '<div class="alert alert-danger">Error: This page requires a url attribute "booking_id".</div>';
    return $content;
  }
  $booking = new CB_Booking($_GET['booking_id']);
  if($booking->booking_status != 'confirmed'){
    $content = $booking->display_booking_details();
    $content .= $booking->display_confirmation_tocart();
    return $content;
  } else {
    $content = '<div class="alert alert-warning"><p>
    Thank you for checking in. It looks like your charter is already confirmed.</p><p>Enjoy your trip!</p></div>';
    $content .= $booking->display_booking_details();
    //$content .= $booking->display_confirmation_tocart();
    return $content;
  }
}



?>
