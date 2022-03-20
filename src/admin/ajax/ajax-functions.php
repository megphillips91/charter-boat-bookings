<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * changelog Mar 2022
 * removed wp_ajax cb_admin_view_callback
 */

add_action( 'wp_ajax_nopriv_cb_open_booking_add', __NAMESPACE__ . '\\cb_open_booking_add_callback' );
add_action( 'wp_ajax_cb_open_booking_add', 'cb_open_booking_add_callback' );

function cb_open_booking_add_callback(){
  $form = new CB_Admin_Form('booking');
  wp_send_json($form);
}

add_action( 'wp_ajax_nopriv_cb_open_booking', __NAMESPACE__ . '\\cb_open_booking_callback' );
add_action( 'wp_ajax_cb_open_booking', __NAMESPACE__ . '\\cb_open_booking_callback' );

function cb_open_booking_callback(){
  $booking = new CB_Admin_Booking(sanitize_text_field($_POST['booking_id']));
  wp_send_json($booking);
}

//cb_admin_ booking order from admin screen
add_action( 'wp_ajax_nopriv_cb_admin_booking_order', __NAMESPACE__ . '\\cb_admin_booking_order_callback' );
add_action( 'wp_ajax_cb_admin_booking_order', __NAMESPACE__ . '\\cb_admin_booking_order_callback' );

function cb_admin_booking_order_callback (){
  $response = array();
  $order = new CB_Admin_Booking_Order(sanitize_text_field($_POST['billing_information']), sanitize_text_field($_POST['product_id']), sanitize_text_field($_POST['date']), sanitize_text_field($_POST['quantity']));
  $response['order'] = $order;
  wp_send_json($response);
}


//cb_admin_show_calendar
add_action( 'wp_ajax_nopriv_cb_admin_show_calendar', __NAMESPACE__ . '\\cb_admin_show_calendar_callback' );
add_action( 'wp_ajax_cb_admin_show_calendar', __NAMESPACE__ . '\\cb_admin_show_calendar_callback' );

function cb_admin_show_calendar_callback(){
  $response = array();
  $date = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
  $products_listing = new CB_List_Products($date->format('Y-m-d'));
  $content .= '<div class="cb-lazyload-global-calendar" date="'.$date->format('Y-m-d').'"><div class="cb-hold-calendar-loader"><span class="fa-stack fa-lg">
  <i class="fa fa-circle fa-stack-2x"></i>
  <i class="fa fa-compass fa-inverse fa-spin fa-stack-2x"></i>
</span></div></div>';
  $content .= '<div>'.$products_listing->html.'</div>';
  $response['html'] = $content;
  wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_admin_filter_booking_status', __NAMESPACE__ . '\\cb_admin_filter_booking_status_callback' );
add_action( 'wp_ajax_cb_admin_filter_booking_status', __NAMESPACE__ . '\\cb_admin_filter_booking_status_callback' );

function cb_admin_filter_booking_status_callback(){
  $response = array();
  $args = array();
  $booking_status = sanitize_text_field($_POST['booking_status']);
  if(session_status() === PHP_SESSION_NONE){session_start();}
  $_SESSION['cb_admin_bookings_status'] = $booking_status;
  $args = array(
    'booking_status' => $booking_status
  );
  $response['args'] = $args;
  $bookings = new CB_Booking_Query($args, 'booking_status');
  $response['bookings']=$bookings;
  $view = (isset($_SESSION['cb_bookings_admin_view']))
    ? $_SESSION['cb_bookings_admin_view']
    : 'table';
  $admin = new CB_Admin_Page($view, $bookings, 'future');
  $response['html']= $admin->html;
  wp_send_json($response);
}

add_action( 'wp_ajax_nopriv_cb_admin_filter_date_range', __NAMESPACE__ . '\\cb_admin_filter_date_range_callback' );
add_action( 'wp_ajax_cb_admin_filter_date_range', __NAMESPACE__ . '\\cb_admin_filter_date_range_callback' );

function cb_admin_filter_date_range_callback(){
  $response = array();
  $args = array();
  /* setting a session variable so it can remember state */
  if(session_status() === PHP_SESSION_NONE){session_start();}
  if(isset($_SESSION['booking_status'])){
    $args['booking_status'] = $_SESSION['booking_status'];
  }
  /* setting which type of booking query */
  $bookings = array();
  if( isset($_POST['date_range']) ){
    $range = sanitize_text_field($_POST['date_range']);
    switch ( $range ){
      case 'future':
        $bookings = new CB_Booking_Query(array('future' => 'future'), 'future');
        break;
      case 'past':
        $bookings = new CB_Booking_Query(array('past' => 'past'), 'past');
        break;
      default:
        $bookings = new CB_Booking_Query(array('future' => 'future'), 'future');
    }
  } else {
    if( isset( $_POST['booking_status'] ) ){
      $status = sanitize_text_field($_POST['date_range']);
      switch ( $status ){
        case 'reserved':
          $bookings = new CB_Booking_Query(array('future' => 'future'), 'future');
          break;
        case 'confirmed':
          $bookings = new CB_Booking_Query(array('past' => 'past'), 'past');
          break;
        default:
          $bookings = new CB_Booking_Query(array('future' => 'future'), 'future');
      }
    }
  }
  
  $response['bookings'] = $bookings;
  $date_range = ( isset($_POST['date_range']) ) ? sanitize_text_field($_POST['date_range']) : 'future';
  $admin_page = new CB_Admin_Page(NULL, $bookings, $date_range);
  $response['html']=$admin_page->html;
  wp_send_json($response);
}

add_action( 'wp_ajax_cb_admin_show_order_links', __NAMESPACE__ . '\\cb_admin_show_order_links_callback' );
function cb_admin_show_order_links_callback(){
  $booking = new CB_Booking($_POST['booking_id']);
  $relative_rez = '/post.php?post='.$booking->orderid_reservation.'&amp;action=edit';
  $relative_balance = '/post.php?post='.$booking->orderid_reservation.'&amp;action=edit';
  $content = '<strong>Reservation Order#:</strong>
  <a target="_blank" href="'.admin_url($relative_rez).'">'.$booking->orderid_reservation.'</a><br>';
  $content .= ($booking->orderid_balance != NULL) ? '<strong>Balance Order#:</strong> <a target="_blank"
  href="'.admin_url($relative_balance).'">'.$booking->orderid_balance.'</a>'.$booking->orderid_balance.'</a>': '';
  $response = array();
  $response['booking'] = $booking;
  $response['html'] = $content;
  wp_send_json($response);
}

?>
