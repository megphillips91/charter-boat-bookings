<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;


add_shortcode('test_addcart', __NAMESPACE__ . '\\test_addcart');
function test_addcart(){
  global $woocommerce;
  //$woocommerce->cart->add_to_cart(11,1);
}


/**
 * Ajax Callback Functions which affect admin and public facing
 */

 /**
  * Add reservation to cart
  * @param $product_id, date
  */
  add_action( 'wp_ajax_nopriv_cb_finalbalance_tocart', __NAMESPACE__ . '\\cb_finalbalance_tocart_callback' );
  add_action( 'wp_ajax_cb_finalbalance_tocart', __NAMESPACE__ . '\\cb_finalbalance_tocart_callback' );

 function cb_finalbalance_tocart_callback(){
   global $woocommerce;
   $response = array();
   $booking = new CB_Booking(sanitize_text_field($_POST['booking_id']));

   //create final balance variation
   $balance_id = cb_variation(
     $booking->product_id,
     $booking->date_object->format('Y-m-d'),
     'finalbalance');
    if($booking->balance_id == NULL){
      $booking->save_balance_id($balance_id);
    }
   //add to cart
   $quantity     = 1;
   $variation    = array(
     'Payment' =>'Final Balance. Your charter is confirmed upon payment completion',
     'Location' =>ucwords($booking->location),
     'Date' => $booking->date_object->format('D, m-d-Y'),
     'Start Time' =>$booking->date_object->format('g:i T'),
     'Duration' =>$booking->duration.' hours',
   );
   $cartitemkey = $woocommerce->cart->add_to_cart(
     $booking->product_id,
     $quantity,
     $booking->balance_id,
     $variation);
   $response['cartitemkey'] = $cartitemkey;
   $response['booking'] = $booking;
   wp_send_json($response);
 }


/**
 * Add reservation to cart
 * @param $product_id, date
 */
 add_action( 'wp_ajax_nopriv_cb_reservation_tocart', __NAMESPACE__ . '\\cb_reservation_tocart_callback' );
 add_action( 'wp_ajax_cb_reservation_tocart', __NAMESPACE__ . '\\cb_reservation_tocart_callback' );

function cb_reservation_tocart_callback(){
  global $woocommerce;
  $response = array();
  $charterdate = new DateTime(sanitize_text_field($_POST['cb_date']));
  $duedate = calc_balance_due_date(sanitize_text_field($_POST['cb_date']));
  $booking_meta = get_charter_booking_meta(sanitize_text_field($_POST['product_id']));
  $variation_id = cb_variation(sanitize_text_field($_POST['product_id']), sanitize_text_field($_POST['cb_date']), 'reservation');
  $quantity     = 1;
  $variation    = array(
    'Location' =>ucwords($booking_meta['_cb_location']),
  	'Date' => sanitize_text_field($_POST['cb_date']),
    'Start Time' =>$booking_meta['_cb_start_time'],
    'Duration' =>$booking_meta['_cb_duration'].' hours',
    'Reservation' =>'This is a reservation only',
    'Final Balance' => '$'.$booking_meta['_cb_final_balance'].' due between '.$duedate.' and boarding to confirm your reservation',
  );

  if($booking_meta['_cb_is_sunset'] == 'yes'){
      $sunset = new CB_Sunset_Time($booking_meta['_cb_location'], sanitize_text_field($_POST['cb_date']));
      $chartertimes = $sunset->get_charter_start_time($booking_meta['_cb_duration']);
      $variation['Start Time']= $chartertimes['boarding'];
      $variation['Sunset Time']= $chartertimes['sunset'];
    }

    /*send to cart*/
    $cartitemkey = $woocommerce->cart->add_to_cart(
    sanitize_text_field($_POST['product_id']),
    $quantity,
    $variation_id,
    $variation);
  $response['cartitemkey'] = $cartitemkey;
  wp_send_json($response);
}

/**
 * Add reservation to cart
 * @param $product_id, date
 */
//add_shortcode('full_charter_to_cart', __NAMESPACE__ . '\\cb_fullcharter_tocart_callback');
 add_action( 'wp_ajax_nopriv_cb_fullcharter_tocart', __NAMESPACE__ . '\\cb_fullcharter_tocart_callback' );
 add_action( 'wp_ajax_cb_fullcharter_tocart', __NAMESPACE__ . '\\cb_fullcharter_tocart_callback' );

function cb_fullcharter_tocart_callback(){
  global $woocommerce;
  $post_date  = sanitize_text_field($_POST['cb_date']);
  $post_product_id = sanitize_text_field($_POST['product_id']);
  $response = array();
  $charterdate = new DateTime($post_date);
  $response['cahrterdate']=$charterdate;
  $duedate = calc_balance_due_date($post_date);
  $booking_meta = get_charter_booking_meta($post_product_id);
  $variation_id = cb_variation($post_product_id, $post_date, 'fullcharter');
  $response['variation'] = $variation_id;
  $quantity     = 1;
  $variation    = array(
    'Location' =>ucwords($booking_meta['_cb_location']),
  	'Date' => $post_date,
    'Start Time' =>$booking_meta['_cb_start_time'],
    'Duration' =>$booking_meta['_cb_duration'].' hours'
  );

  if($booking_meta['_cb_is_sunset'] == 'yes'){
      $sunset = new CB_Sunset_Time($booking_meta['_cb_location'], $post_date);
      $chartertimes = $sunset->get_charter_start_time($booking_meta['_cb_duration']);
      $variation['Start Time']= $chartertimes['boarding'];
      $variation['Sunset Time']= $chartertimes['sunset'];
    }

    /*send to cart*/
    $cartitemkey = $woocommerce->cart->add_to_cart(
    $post_product_id,
    $quantity,
    $variation_id,
    $variation);
  $response['cartitemkey'] = $cartitemkey;
  wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_refresh_calendar', __NAMESPACE__ . '\\cb_refresh_calendar_callback' );
add_action( 'wp_ajax_cb_refresh_calendar', __NAMESPACE__ . '\\cb_refresh_calendar_callback' );

function cb_refresh_calendar_callback(){
  $post_date = sanitize_text_field($_POST['date']);
  $post_type = sanitize_text_field($_POST['type']);
  $post_list_action = sanitize_text_field($_POST['list_action']);
  $post_product_id = sanitize_text_field($_POST['product_id']);
  $response = array();
  $month = date('n', strtotime(($post_date)));
  $year = date('Y', strtotime(($post_date)));
  $type = $post_type;

  if($type == 'global'){
    $calendar = new CB_Global_Calendar('global', $month, $year, NULL, $post_list_action);
    wp_send_json($calendar->html);
  }
  if($type == 'product'){
    $calendar = new CB_Product_Calendar('product', $month, $year, $post_product_id);
    wp_send_json($calendar->html);
  }
}

add_action( 'wp_ajax_nopriv_cb_delete_order', __NAMESPACE__ . '\\cb_delete_order_callback' );
add_action( 'wp_ajax_cb_delete_order', __NAMESPACE__ . '\\cb_delete_order_callback' );

function cb_delete_order_callback(){
  global $wpdb;
  $qry = "select * from ".$wpdb->prefix."posts where post_name LIKE '%reservation%' || post_name LIKE '%balance%'";
  $posts = $wpdb->query($qry);
  foreach($posts as $product_post){
    $product = wc_get_product($product_post->ID);
    $product->set_stock(1);
  }
}

add_action( 'wp_ajax_nopriv_cb_get_sunset', __NAMESPACE__ . '\\cb_get_sunset_callback' );
add_action( 'wp_ajax_cb_get_sunset', __NAMESPACE__ . '\\cb_get_sunset_callback' );

function cb_get_sunset_callback(){
  //35.3522° N, 75.5108° W
  $sunset = new CB_Sunset_Time('avon', '2019-08-31');
  $before = $sunset->get_charter_start_time(2.5);
  wp_send_json($before);
}





 ?>
