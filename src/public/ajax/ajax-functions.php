<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

add_action( 'wp_ajax_nopriv_cb_lazyload_global_calendar', __NAMESPACE__ . '\\cb_lazyload_global_calendar_callback' );
add_action( 'wp_ajax_cb_lazyload_global_calendar', __NAMESPACE__ . '\\cb_lazyload_global_calendar_callback' );

function cb_lazyload_global_calendar_callback(){
  foreach($_POST as $key=>$value){
    $$key = sanitize_text_field($value);
  }
  $list_action = NULL;
  $response = array();
  $calendar = new CB_Global_Calendar(NULL, NULL, NULL, NULL, $list_action);
  $response=$calendar;
  wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_lazyload_product', __NAMESPACE__ . '\\cb_lazyload_product_callback' );
add_action( 'wp_ajax_cb_lazyload_product', __NAMESPACE__ . '\\cb_lazyload_product_callback' );

function cb_lazyload_product_callback(){
  foreach($_POST as $key=>$value){
    $$key = sanitize_text_field($value);
  }
  $response=array('success');
  $timezone = get_option('timezone_string');
  $product_id = $product_id;
  $list_action = (empty($list_action)) ? NULL : $list_action;
  $product_listing = new CB_List_Product($product_id, $cb_date, NULL, $list_action);
  $html = $product_listing->html;
  $response['product-listing'] = $product_listing;
  $response['html']=$html;
  wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_list_product', __NAMESPACE__ . '\\cb_list_product_callback' );
add_action( 'wp_ajax_cb_list_product', __NAMESPACE__ . '\\cb_list_product_callback' );

function cb_list_product_callback(){
  foreach($_POST as $key=>$value){
    $$key = sanitize_text_field($value);
  }
  $timezone = get_option('timezone_string');
  $product_listing = new CB_List_Product($product_id, $cb_date, 'yes');
  $html = '<div class="hold-product-listing">'.$product_listing->html.'</div>';
  $response=array();
  $response['product-listing'] = $product_listing;
  $response['html']=$html;
  wp_send_json($response);
}


add_action( 'wp_ajax_nopriv_cb_list_product_tomorrow', __NAMESPACE__ . '\\cb_list_product_tomorrow_callback' );
add_action( 'wp_ajax_cb_list_product_tomorrow', __NAMESPACE__ . '\\cb_list_product_tomorrow_callback' );

function cb_list_product_tomorrow_callback(){
  foreach($_POST as $key=>$value){
    $$key = sanitize_text_field($value);
  }
  $timezone = get_option('timezone_string');
  $date = new DateTime($cb_date, new DateTimeZone($timezone));
  $date->add(new DateInterval('P1D'));
  $product_id = $product_id;
  $product_listing = new CB_List_Product($product_id, $date->format('Y-m-d'), 'yes');
  $html = '<div class="hold-product-listing">'.$product_listing->html.'</div>';
  $response=array();
  $response['product-listing'] = $product_listing;
  $response['html']=$html;
  wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_list_product_yesterday', __NAMESPACE__ . '\\cb_list_product_yesterday_callback' );
add_action( 'wp_ajax_cb_list_product_yesterday', __NAMESPACE__ . '\\cb_list_product_yesterday_callback' );

function cb_list_product_yesterday_callback(){
  foreach($_POST as $key=>$value){
    $$key = sanitize_text_field($value);
  }
  $timezone = get_option('timezone_string');
  $date = new DateTime($cb_date, new DateTimeZone($timezone));
  $date->sub(new DateInterval('P1D'));
  $product_id = $product_id;
  $product_listing = new CB_List_Product($product_id, $date->format('Y-m-d'), 'yes');
  $html = '<div class="hold-product-listing">'.$product_listing->html.'</div>';
  $response=array();
  $response['product-listing'] = $product_listing;
  $response['html']=$html;
  wp_send_json($response);
}

 ?>
