<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * Woo Booking
 *
 * Aligns to WooCommerce Orders and Order Items. All plugin functions that are called by WC Hooks and interface with WooCommerce to create and administrate orders of bookings.
 */

/**
 * CB Payment Complete
 *
 * when the payment is completed successfully, loop through booking items within this order and
 *  -- create or instantiate booking
 *  -- set status of booking
 *  -- set wc order status to completed (because the booking items are sold individually this holds to logic)
 *
 * @param  int $order_id woocommerce order_id
 *
 */
function cb_payment_complete($order_id){
  $order = wc_get_order($order_id);
  $order_items = new CB_Order_Items($order_id);
  foreach($order_items->reservations as $booking){
    if(cb_item_is_full_charter($booking->reservation_id)) {
       $booking->set_booking_status('confirmed');
     } else {
       $booking->set_booking_status('reserved');
     }
    $order->update_status( 'completed' );
    wc_update_order_item_meta(
      $booking->order_item_id,
      'Reservation Number', $booking->id );
    //$booking->set_persons($order_item);
  }
  foreach($order_items->confirmations as $booking){
    $order->update_status( 'completed' );
    $booking->save_orderid_balance($order_id);
    $booking->set_booking_status('confirmed');
    wc_update_order_item_meta(
      $booking->order_item_id,
      'Reservation Number', $booking->id );
  }
}
add_action('woocommerce_payment_complete', __NAMESPACE__ . '\\cb_payment_complete');

/**
 * Order status to cancelled
 *
 * loop through booking order items and perform the proper booking related inventory functions and deletions when order status is changed to cancelled
 *
 * @var int order_id
 */
function cb_cancel_bookings ($order_id){
  $order = wc_get_order($order_id);
  $items = new CB_Order_Items($order_id);
  foreach($items->reservations as $booking){
    $booking->delete();
  }
  foreach($items->confirmations as $booking){
    $booking->set_booking_status('reserved');
    $booking->save_orderid_balance(NULL);
  }
}
add_action( 'woocommerce_order_status_cancelled', __NAMESPACE__ . '\\cb_delete_bookings', 10, 1 );

/**
 * Before Post Delete
 *
 * When an admin deletes a booking order loop through booking order items and perform the proper booking related inventory functions and deletions
 *
 * @var int order_id
 */

function cb_delete_bookings ($order_id){
  $order = wc_get_order($order_id);
  $items = new CB_Order_Items($order_id);
  foreach($items->reservations as $booking){
    if($booking->has_persons){
      $booking->delete();
      wc_update_product_stock( $booking->reservation_id, $booking->persons, 'increase' );
    } else {
      $booking->delete();
      wc_update_product_stock( $booking->reservation_id, 1, 'increase' );
    }
  }
  foreach($items->confirmations as $booking){
    $booking->set_booking_status('reserved');
    $booking->save_orderid_balance(NULL);
    if($booking->has_persons){
      wc_update_product_stock( $booking->balance_id, $booking->persons, 'increase' );
    } else {
      wc_update_product_stock( $booking->balance_id, 1, 'increase' );
    }
  }
}
add_action('before_delete_post', __NAMESPACE__ . '\\cb_delete_bookings', 10, 1);

/**
 * is wc product a reservation?
 *
 * @param  int  $variation_id - wooocommerce variation id - post id of the product variation
 * @return boolean
 */
function cb_item_is_reservation($variation_id){
  $item_type = get_post_meta($variation_id, 'attribute_pa__cb_type', true);
  if($item_type == 'reservation'){
    return true;
  } else {
    return false;
  }
}

/**
 * is wc product a booking confirmation (i.e. final balance payment)?
 *
 * @param  int  $variation_id - wooocommerce variation id - post id of the product variation
 * @return boolean
 */
function cb_item_is_final_balance($variation_id){
  $item_type = get_post_meta($variation_id, 'attribute_pa__cb_type', true);
  if($item_type == 'finalbalance'){
    return true;
  } else {
    return false;
  }
}

/**
 * is wc product a full charter reservation (i.e. total price payment)?
 *
 * @param  int  $variation_id - wooocommerce variation id - post id of the product variation
 * @return boolean
 */
function cb_item_is_full_charter($variation_id){
  $item_type = get_post_meta($variation_id, 'attribute_pa__cb_type', true);
  if($item_type == 'fullcharter'){
    return true;
  } else {
    return false;
  }
}
/**
 * when product is saved, check if should be sold individually or not.
 * this is to be used with 'persons' charters and set up to support inventory management of per person charters
 * @var [type]
 */
add_action('save_post_product', __NAMESPACE__ . '\\cb_set_sold_individually', 10, 3);
function cb_set_sold_individually( $post_id, $post, $update ) {
    $product = wc_get_product( $post_id );
    (is_charter_booking($post_id))
    ? $product->set_sold_individually(true)
    : $product->set_sold_individually(false);
    // do something with this product
}

/**
 * Send Invoice Out to Customer
 *
 * If you create a new order programmatically, this will send out the standard woocommerce invoice order details email tot he customer. The customer will need to click through and process thier credit card.
 *
 * @param  int $order_id wooocommerce order it
 *
 */
function cb_send_invoice_email($order_id) {
    $order = wc_get_order($order_id);

    wc_switch_to_site_locale();

    do_action('woocommerce_before_resend_order_emails', $order);

    // Ensure gateways are loaded in case they need to insert data into the emails.
    WC()->payment_gateways();
    WC()->shipping();

    // Load mailer.
    $mailer = WC()->mailer();
    $email_to_send = 'customer_invoice';
    $mails = $mailer->get_emails();

    if (!empty($mails)) {
        foreach ($mails as $mail) {
            if ($mail->id == $email_to_send) {
                $mail->trigger($order->get_id(), $order);
                $order->add_order_note( sprintf( __('%s email notification manually sent.', 'woocommerce'), $mail->title), false, true);
            }
        }
    }

    do_action('woocommerce_after_resend_order_email', $order, $email_to_send);

    wc_restore_locale();
}

/**
 * create coupon code
 */

function cb_create_coupon($coupon_code, $amount, $discount_type, $usage_limit){
  $coupon = array(
  	'post_title' => $coupon_code,
  	'post_content' => '',
  	'post_status' => 'publish',
  	'post_author' => 1,
  	'post_type'		=> 'shop_coupon'
  );
  $new_coupon_id = wp_insert_post( $coupon );
  // Add meta
  update_post_meta( $new_coupon_id, 'discount_type', $discount_type );
  update_post_meta( $new_coupon_id, 'coupon_amount', $amount );
  update_post_meta( $new_coupon_id, 'individual_use', 'no' );
  update_post_meta( $new_coupon_id, 'product_ids', '' );
  update_post_meta( $new_coupon_id, 'exclude_product_ids', '' );
  update_post_meta( $new_coupon_id, 'usage_limit', $usage_limit );
  update_post_meta( $new_coupon_id, 'expiry_date', '' );
  update_post_meta( $new_coupon_id, 'apply_before_tax', 'yes' );
  update_post_meta( $new_coupon_id, 'free_shipping', 'no' );
  return $new_coupon_id;
}

/**
 * Register new status
 *
**/
function register_rescheduled_order_status() {
    register_post_status( 'wc-rescheduled', array(
        'label'                     => 'Rescheduled',
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Rescheduled', 'Rescheduled' )
    ) );
}
add_action( 'init', __NAMESPACE__ . '\\register_rescheduled_order_status' );

// Add to list of WC Order statuses
function add_rescheduled_to_order_statuses( $order_statuses ) {

    $new_order_statuses = array();

    // add new order status after processing
    foreach ( $order_statuses as $key => $status ) {

        $new_order_statuses[ $key ] = $status;

        if ( 'wc-processing' === $key ) {
            $new_order_statuses['wc-rescheduled'] = 'Rescheduled';
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', __NAMESPACE__ . '\\add_rescheduled_to_order_statuses' );

// Admin reports for custom order status
function cb_add_rescheduled_to_reports( $args ) {
    //$args['order_status'] = array( 'rescheduled', 'completed', 'processing', 'on-hold', 'awaiting-shipment', 'dispatched' );
    $args['order_status'][]= 'rescheduled';
    return $args;
};
add_filter( 'woocommerce_reports_get_order_report_data_args', __NAMESPACE__ . '\\cb_add_rescheduled_to_reports');

?>
