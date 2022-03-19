<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * CB Booking Class
  *
  * main class that holds booking information
  *
  * @param int booking_id -- to instantiate directly, you can pass in the booking id. The booking object can be instantiated, but bot created directly.
  */

class CB_Booking {
    public $id; //charter id
    public $user_id;
    public $product_id; //product id of the parent product
    public $product_name;
    public $charter_date;
    public $date_object;
    public $duration;
    public $location;
    public $persons;
    public $has_persons;
    public $reservation_id; //variation_id of the reservation product
    public $orderid_reservation;
    public $final_balance;
    public $balance_due_date;
    public $balance_id; //variation_id of the final balance product
    public $orderid_balance;
    public $booking_status;
    public $billing_email;
    public $billing_phone;
    public $order_item_id;
    public $reservation_total;

    public function __construct($id = NULL){
      if($id != NULL){
        global $wpdb;
        $booking = $wpdb->get_row("select * from wp_charter_bookings where id = $id LIMIT 1");
        if($booking){foreach($booking as $key=>$value){
          $this->$key = $value;
        }}
        $this->set_date_object();
        $this->set_product_details();
        $this->set_duration_hours_mins();
        $this->set_end_time();
        $this->set_balance_due_date();
        $this->set_final_balance();
        $this->set_payment_status();
        $this->set_userid();
      }
    }

    private function set_product_details(){
      $product = wc_get_product($this->product_id);
      if($product){
      $this->product_name = $product->get_name();
      $this->has_persons = FALSE;
      if($this->has_persons == false){
        $this->capacity = get_option('cb_booking_capacity');
      }
    }
    }

    private function set_date_object(){
      $timezone = get_option('timezone_string');
      $this->date_object = new DateTime($this->charter_date, new DateTimeZone($timezone));
    }

    private function set_end_time(){
      $enddate = new DateTime($this->charter_date, new DateTimeZone(get_option('timezone_string')));
      if (strpos($this->duration, '.') !== false){
        $enddate->add(new DateInterval("PT".$this->duration_hours."H".$this->duration_minutes."M"));
      } else {
        $enddate->add(new DateInterval("PT".$this->duration_hours."H"));
      }
      $this->end_time = $enddate->format("Y-m-d H:i:s");
    }

    private function set_duration_hours_mins(){
      $duration = (float)$this->duration;
      $this->duration_hours = floor($duration);
      $this->duration_minutes = ($duration-$this->duration_hours)*60;
    }

    private function set_balance_due_date(){
      $balance_due_date = get_post_meta($this->reservation_id, 'attribute_pa__cb_balance_due_date', true);
      $timezone = get_option('timezone_string');
      $balance_dd = new DateTime($balance_due_date, new DateTimeZone($timezone));
      $this->balance_due_date = $balance_dd->format('m-d-Y');
      $now = new DateTime("now", new DateTimeZone($timezone));
      if($balance_dd->format('Y-m-d') >= $now->format('Y-m-d')){
        $this->balanceduedate_in_future = true;
      } else {
        $this->balanceduedate_in_future = false;
      }
    }

    private function set_final_balance(){
      $this->final_balance = get_post_meta($this->reservation_id, 'attribute_pa__cb_balance_due', true);
    }

    private function set_userid(){
      $order = wc_get_order($this->orderid_reservation);
      $this->user_id = ($order) ? $order->get_user_id() : '';
      $user = get_user_by('ID', $this->user_id);
      $this->billing_name = ($order)
        ? $order->get_billing_first_name().' '.$order->get_billing_last_name()
        : '';
      $this->set_order_total('reservation', $order);
    }

    public function save_balance_id($balance_id){
      global $wpdb;
      $wpdb->update(
        'wp_charter_bookings',
        array('balance_id'=>$balance_id),
        array('id'=>$this->id)
      );
      $this->balance_id = $balance_id;
    }

    public function save_orderid_balance($orderid_balance){
      global $wpdb;
      $wpdb->update(
        'wp_charter_bookings',
        array('orderid_balance'=>$orderid_balance),
        array('id'=>$this->id)
      );
      $this->orderid_balance = $orderid_balance;
      $order = wc_get_order( $orderid_balance );
      $this->set_order_total('confirmation', $order);

    }

    public function set_booking_status($status){
      global $wpdb;
      $wpdb->update(
        'wp_charter_bookings',
        array('booking_status'=>$status),
        array('id'=>$this->id)
      );
      $this->booking_status = $status;
    }

    public function set_order_item_id($item_id){
      $this->order_item_id = $item_id;
    }

    public function set_order_total($type, $order_object){
      $attrname = $type.'_total';
        if($order_object){
        $this->$attrname = $order_object->get_formatted_order_total();
      }
    }

    public function update($field, $value){
      global $wpdb;
      if($this->field != $value){
        $wpdb->update(
          'wp_charter_bookings',
          array($field=>$value),
          array('id'=>$this->id)
        );
      }
    }

    private function set_payment_status(){
      if($this->booking_status == 'reserved'){
        $this->payment_status = ($this->orderid_balance == NULL)
          ? 'final_balance_pending'
          : 'final_balance_paid';
      } else {
        $this->payment_status = 'full_charter_paid';
      }
    }

    /**
     * Display methods
     */
    public function display_booking_details(){
        $content = '<div class="booking-meta">';
        $content .= '<h3>'.get_the_title($this->product_id).'</h3>';
        $content .= '<p><span class="meta-title">Location: </span>'.$this->location.'</p>';

        $content .= '<p><span class="meta-title">Date: </span>'.$this->date_object->format('D, m-d-Y').'</p>';
        $content .= '<p><span class="meta-title">Time: </span>'.$this->date_object->format('g:i A T').'</p>';
        $content .= '<p><span class="meta-title">Duration: </span>'.$this->duration.' Hours</p>';
        //$content .= '<div class="alert alert-caution>Your charter is coming up, the final balance of '.$this->final_balance.'is due now. Click below to confirm your charter with a final payment. </div>"';
        $content .=  '</div>';
        return $content;
    }

    /* display add to cart button */
    public function display_confirmation_tocart(){
      $content = '
      <p>Your charter is coming up, the final balance of $'.$this->final_balance.' is due now. Click below to confirm your charter with a final payment.</p>';
      $content .= '<p><a class="btn btn-light cb-finalbalance-tocart" booking_id="'.$this->id.'">Confirm Charter</a></p>';
      return $content;
    }

    public function delete(){
      global $wpdb;
      $wpdb->delete(
        'wp_charter_bookings',
        array('id'=>$this->id)
      );
    }

    public function set_persons($persons){
      $this->persons = $persons;
      global $wpdb;
      $wpdb->update(
        'wp_charter_bookings',
        array('persons'=>$persons),
        array('id'=>$this->id)
      );
    }

  }//end CB Booking Class declaration

/**
 * Booking Factory /**
  *
  * useful for initiating or creating a booking within the WC Order space. The Booking class requires the booking ID to instantiate directly. You cannot create a booking by instantiating the booking class.
  *
  * @param string (required) type accepts either 'reservation' or 'finalbalance'
  * @param int (required) variation_id is the WC product variation id.
  * @param int (optional) order_id is the WC Orderid.
  *
  * @return object CB_Booking
  *
  */

class CB_Booking_Factory  {

  public function make_booking($type, $variation_id, $orderid = NULL){
    $this->orderid = $orderid;
    switch ($type){
      case 'reservation' :
        $reservation_id = $variation_id;
        if($this->booking_exists($reservation_id)){
          $booking = $this->instantiate_by_reservation($reservation_id);
          return $booking;
        } else {
          $id = $this->create_new_booking($reservation_id, $orderid);
          $booking = new CB_Booking($id);
          return $booking;
        }
        break;
      case 'finalbalance':
        $balance_id = $variation_id;
        $booking = $this->instantiate_by_finalbalance($balance_id);
        return $booking;
      case 'total':
        //what to do?
        if($this->booking_exists($reservation_id)){
          $booking = $this->instantiate_by_reservation($reservation_id);
          return $booking;
        } else {
          $id = $this->create_new_booking($reservation_id, $orderid);
          $booking = new CB_Booking($id);
          return $booking;
        }
    }

  }


/**
* constructs the booking by the reservation_id
*
* use this factory method when the booking_id is unavailable and yet you still need to access a booking object; most useful when in woocommerce order context wherein you have item details, but no other details about the booking itself.
*
* @param  int $variation_id wooocommerce variation id of the reservation product
* @return object the booking
*/
  private function instantiate_by_reservation($reservation_id){
    global $wpdb;
    $booking = $wpdb->get_row("select * from wp_charter_bookings where reservation_id = $reservation_id");
    $booking = new CB_Booking($booking->id);
    return $booking;
  }


/**
 * constructs the booking by the balance_id
 *
 * use this factory method when the booking_id is unavailable and yet you still need to access a booking object; most useful when in woocommerce order context wherein you have item details, but no other details about the booking itself.
 *
 * @param  int $variation_id wooocommerce variation id of the final balance product
 * @return object the booking
 */
  private function instantiate_by_finalbalance($balance_id){
    global $wpdb;
    $booking = $wpdb->get_row("select * from wp_charter_bookings where balance_id = $balance_id");
    $booking = new CB_Booking($booking->id);
    return $booking;
  }

/**
 * Booking Exists
 * @param  int $variation_id wooocommerce variation id of the reservation product
 * @return bool
 */
  private function booking_exists($reservation_id){
    $product = wc_get_product($reservation_id);
    if($product){
      $has_persons = FALSE ;
      if(!$has_persons){
        //then query for any booking of this variation.
        global $wpdb;
        $booking = $wpdb->get_row("select * from wp_charter_bookings where reservation_id = $reservation_id");
        if($booking == NULL){
          return false;
        } else {
          return true;
        }
    } else {// is a per person charter
      //the only way to determine if the booking exists is what? i suppose the same person on the same variation...
      ////we are not trying to control inventory here. because we have already done that through woocommerce. they couldnt get here if the inventory didnt exist. so int his case, we are going to return false on any per person charter and see how that goes for now.
        global $wpdb;
        $booking = $wpdb->get_row("select * from
          wp_charter_bookings where
          reservation_id = $reservation_id && orderid_reservation = ".$this->orderid);
        if($booking == NULL){
          return false;
        } else {
          return true;
        }
    }
  }
}

/**
 * Create new booking
 *
 * inserts a new booking into the wpdb
 *
 * @param  int $reservation_id the woocommerce variation id of the reservation product for this booking
 *
 */
  private function create_new_booking($reservation_id, $order_id = NULL){
    $args = $this->set_charter_args($reservation_id);
    $args = $this->set_order_args($order_id, $args);
    global $wpdb;
    $wpdb->insert(
      'wp_charter_bookings',
      $args
    );
    return $wpdb->insert_id;
  }

  private function set_charter_args($reservation_id){
    $charter_args = array();
    $charter_args['reservation_id'] = $reservation_id;
    //product_id
    $product = wc_get_product($reservation_id);
    $product_id = $product->get_parent_id();
    $charter_args['product_id'] = $product_id;
    //duration
    $charter_args['duration'] = get_post_meta($product_id, '_cb_duration', true);
    //location
    $charter_args['location'] = get_post_meta($product_id, '_cb_location', true);
    //date
    $charter_args['charter_date'] = $this->get_charter_datetime($reservation_id);
    $charter_args['booking_status'] = (cb_item_is_full_charter($reservation_id))
      ? 'confirmed'
      : 'reserved';
    return $charter_args;
  }

  private function set_order_args($order_id, $args){
    if($order_id != NULL){
      $order = wc_get_order($order_id);
      $args['orderid_reservation'] = $order_id;
      $args['billing_email'] = $order->get_billing_email();
      $args['billing_phone'] = $order->get_billing_phone();
    }
    return $args;
  }

  private function get_charter_datetime($reservation_id){
    $date = get_post_meta($reservation_id, 'attribute_pa__cb_date', true);
    $start = get_post_meta($reservation_id, 'attribute_pa__cb_start_time', true);
    $start_time = $this->get_start_time_name($start);
    $timezone = get_option('timezone_string');
    $datetime = $date.' '.$start_time;
    $date = new DateTime($date.' '.$start_time.' ', new DateTimeZone($timezone));
    $datetime_string = $date->format("Y-m-d H:i:s");
    return $datetime_string;
  }

  /**
   * get start time from slug
   *
   * The attribute stores the slug of the meta tag rather than the name. This is a woocommerce idiosyncrasy. The attributes are actually wp taxonomies. The name is the actual time, but the post meta stores the slug.  Therefore, this function takes in the slug and returns the name which is a recognizable datetime format
   *
   * @param  string $start the attribute slug from get-post-meta
   * @return string $time which is parsable as datetime
   */
   private function get_start_time_name($slug){
     $args = array(
      'hierarchical' => false,
      'label' => 'Start Times',
      'show_ui' => false,
      'query_var' => true,
      'singular_label' => 'Start Time'
      );
      register_taxonomy('pa__cb_start_time', array('product'), $args);
      $term = get_term_by('slug', $slug, 'pa__cb_start_time');
     if($term){return $term->name;}
   }

} //end cb booking factory class declaration


class CB_Booking_Change extends CB_Booking{

  public $store_credit;
  public $remaining_balance;
  public $coupon_id;

  public function revise_schedule($new_product_id, $new_date){
    $this->new_product_id = $new_product_id;
    $this->new_date = $new_date;
    $this->new_date_object = new DateTime($this->new_date, new DateTimeZone(get_option('timezone_string')));
    //handle coupon creation and price change
    $this->set_store_credit();
    $this->create_coupon();
    $this->set_quantity();
    $this->cancel_original_order();
    $this->create_new_order();
    $this->set_remaining_balance();
    $this->send_notifications();
  }

  private function cancel_original_order(){
    $order = wc_get_order($this->orderid_reservation);
    $order->update_status('wc-rescheduled');
    $this->set_booking_status('rescheduled');
  }

  private function set_quantity(){
    $new_product = wc_get_product($this->new_product_id);
    if($new_product->get_sold_individually() == true ){
      $this->quantity = 1;
    } else {
      $this->quantity = $this->persons();
    }
  }

  private function create_new_order(){
    $new_order = wc_create_order();
    $type = ($this->booking_status == 'confirmed')
      ? 'fullcharter'
      : 'reservation';
    $address = $this->set_customer_address();
    $new_order->set_address( $address, 'billing' );
    update_post_meta($new_order->get_id(), '_customer_user', $this->user_id);
    $cb_order = new CB_Order_Items($new_order->get_id());
    $new_variation_id = $cb_order->add_product($this->new_product_id, $this->quantity, $this->new_date, $this->new_type());
    $this->new_reservation_id = $new_variation_id;
    $new_order->calculate_totals();
    $this->new_order_value = $new_order->get_total();
    $new_order->apply_coupon($this->coupon_code);
    $new_order->calculate_totals();
    $new_order_status = ($new_order->get_total() <= 0) ? 'completed' : 'pending' ;
    $new_order->update_status($new_order_status);
    if($new_order->get_total() <= 0){
      //need to fire a new booking on payment complete action....because it doesnt fire for orders of 0 value that never actually process through stripe.
      cb_payment_complete($new_order->get_id());
      $this->set_remaining_store_credit();
      //need to calculate the remaining store credit and set that into this class.
    }
    $this->new_order = wc_get_order($new_order->get_id());
    $this->new_order_id = $new_order->get_id();
  }

  private function new_type(){
    $today_object = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $today_object->add(new DateInterval('P3D'));
    if($this->new_date_object > $today_object){
      $type = 'reservation';
    } else {
      $type = 'fullcharter';
    }
    return $type;
  }

  private function set_remaining_store_credit(){
    $original_order = wc_get_order($this->orderid_reservation);
    $original_total = $original_order->get_total();
    $remaining_credit = $this->new_order_value - $original_total;
    $this->remaining_credit = $remaining_credit;
  }

  private function set_store_credit(){
    $order = wc_get_order($this->orderid_reservation);
    if($order){
      $total = $order->get_total();
      $this->original_reservation_total = $total;
      $store_credit = $total;
      //if balance has been paid
      if($this->orderid_balance != NULL){
        $final_order = wc_get_order($this->orderid_balance);
        $this->original_balance_total = $final_order->get_total();
        $store_credit = $store_credit + $this->original_balance_total;
      } else {
        $this->original_balance_total = 0;
      }
      $this->store_credit = $store_credit;
    }
  }

  private function create_coupon(){
    $coupon_code = $this->id.'_store_credit_'.rand();
    $coupon_id = cb_create_coupon(
      $coupon_code,
      $this->store_credit,
      'fixed_cart',
      1);
    $this->coupon_id = $coupon_id;
    $this->coupon_code = $coupon_code;
  }

  private function set_remaining_balance(){
    $original_total = $this->original_reservation_total + $this->original_balance_total;
    $new_total = $this->new_order->get_total();
    $this->remaining_balance = $this->new_order_value - $this->store_credit;
  }

  private function apply_coupon(){
    $this->new_order->apply_coupon($this->coupon_id);
  }

  private function new_booking_details(){
    $this->new_booking_meta = get_charter_booking_meta($this->new_product_id);
    $this->new_starttime = cb_get_product_starttime($this->new_product_id, $this->new_date);
  }

  private function set_balance_due_date(){
    //after adjust the booking information, go in and update the balance due date in booking table
  }

  private function send_notifications(){
    switch ($this->remaining_balance){
      case $this->remaining_balance  == 0 :
        $this->send_customer_reschedule_notification();
        break;
      case $this->remaining_balance > 0 :
        $this->send_invoice();
        $this->send_customer_reschedule_notification();
        break;
      case $this->remaining_balance < 0 :
        $this->send_refund_reminder();
        $this->send_customer_reschedule_notification();

        break;
    }
  }

  private function send_invoice(){
    $mailer = WC()->mailer();
    $mails = $mailer->get_emails();
    if ( ! empty( $mails ) ) {
        foreach ( $mails as $mail ) {
            if ( $mail->id == 'customer_invoice' ) {
               $mail->trigger( $this->new_order_id );
            }
         }
    }
  }

  private function send_refund_reminder(){
    $customer_fname = $this->new_order->get_billing_first_name();
    $customer_lname = $this->new_order->get_billing_last_name();
    $subject = $customer_fname.' '.$customer_lname.' has a remaining credit';
    $message = '<p>'.$customer_fname.' '.$customer_lname."'".'s charter has been rescheduled to a charter of less total value. A store credit was issued and applied to the new booking, but there is a remaining credit. You will need to issue a refund directly through your card processor. This cannot be done through the WooCommerce Admin.  </p>';
    $refund = abs($this->remaining_credit);
    $message .= '<p>Refund Amount: '.$refund.
    '<br>Original Order# '.$this->orderid_reservation.
    '<br>New Order# '.$this->new_order_id.'<p>';
    $this->adminmessage = $message;
    $notification = new CB_Admin_Notification($subject, $message);
    $this->admin_notification = $notification;
    $notification->send();
  }

  private function send_customer_reschedule_notification (){
    $this->new_booking_details();
    $message = "<p>We've gone ahead and changed your cruising schedule as discussed. Looking forward to our time together. Your original order has been rescheduled, and a new order has been processed. Expect a couple emails from our system notifying you of the change. Please do not be alarmed if these emails are delivered out of sequence. </p>";
    $message .= ($this->remaining_balance > 0)
      ? '<p>There is a balance resulting from the change. Please check your emails for the invoice. You can click the link to pay the balance due. </p>'
      : '' ;
    $message .= '<p>Please feel free to reach out to me directly if you have any questions. </p>';
    $message .= '<strong>New Schedule</strong>';
    $message .= '<p>Date: '.$this->new_starttime->format('D, M j, Y');
    $message .= '<br>Start Time: '.$this->new_starttime->format('H:i a T');
    $message .= '<br>Duration: '.$this->new_booking_meta['_cb_duration'];
    $message .= '<br>Location: '.$this->new_booking_meta['_cb_location'].'</p>';
    $this->customermessage = $message;
    $notification = new CB_Customer_Notification($this->id, 'Cruising Schedule Changed', $message);
    $this->customer_notification = $notification;
    $notification->send();
  }

  private function set_customer_address(){
    $original_order = wc_get_order($this->orderid_reservation);
      $address = array(
        'first_name' => $original_order->get_billing_first_name(),
        'last_name'  => $original_order->get_billing_last_name(),
        'company'    => $original_order->get_billing_company(),
        'email'      => $original_order->get_billing_email(),
        'phone'      => $original_order->get_billing_phone(),
        'address_1'  => $original_order->get_billing_address_1(),
        'address_2'  => $original_order->get_billing_address_2(),
        'city'       => $original_order->get_billing_city(),
        'state'      => $original_order->get_billing_state(),
        'postcode'   => $original_order->get_billing_postcode(),
        'country'    => $original_order->get_billing_country()
      );
      $this->customer_address = $address;
      return $address;
  }

  private function change_original_order_dep($new_product_id, $new_date){
    $cb_order = new CB_Order_Items($this->orderid_reservation);
    $cb_order->remove_product($this->product_id, $this->reservation_id);
    $type = ($this->booking_status == 'confirmed') ? 'fullcharter' : 'reservation';
    $new_variation_id = $cb_order->add_product($new_product_id, $this->quantity, $new_date, $type);
    $this->new_order = wc_get_order($this->orderid_reservation);
    $this->new_order->calculate_totals();
    $this->new_order->apply_coupon($this->coupon_id);
    $this->new_order->calculate_totals();
    return $new_variation_id;
  }

} //end cb booking change declaration


/**
 * Admin Booking Order (ADD BOOKING)
 *
 * for phone orders, etc. pass in the information needed - create order, send invoice to customer.
 * Booking will be created on payment complete so calendar will not be marked unavailable until charter order is paid for
 *
 * @param array $billing_information - see woocommerce billing information fields
 */
class CB_Admin_Booking_Order {
  public $product_id;
  public $date;
  public $order_id;
  public $order;
  private $billing_information;


  public function __construct($billing_information, $product_id, $date, $quantity = 1 ){
    $this->quantity = $quantity;
    $this->billing_information = $billing_information;
    $this->product_id = $product_id;
    $this->date = $date;
    $this->create_order();
    $this->set_billing_information();
    $this->send_invoice();
  }

  private function create_order(){
    $new_order = wc_create_order();
    $this->set_order_billing($new_order);
    //$new_order->set_address( $this->billing_information, 'billing' );
    $cb_order = new CB_Order_Items($new_order->get_id());
    $type = cb_get_type($this->date);
    $new_variation_id = $cb_order->add_product($this->product_id, $this->quantity, $this->date, $type);
    $new_order->calculate_totals();
    $new_order_status = ($new_order->get_total() <= 0) ? 'completed' : 'pending' ;
    $new_order->update_status($new_order_status);
    $this->order = $new_order;
    $this->order_id = $new_order->get_id();
  }

  private function set_order_billing($order){
    $order->set_address( $this->billing_information, 'billing' );
    $email = $order->get_billing_email();
    $user = get_user_by('email', $email);
    if($user){update_post_meta($order->get_id(), '_customer_user', $user->data->ID);}
  }

  private function send_invoice(){
    $mailer = WC()->mailer();
    $mails = $mailer->get_emails();
    if ( ! empty( $mails ) ) {
        foreach ( $mails as $mail ) {
            if ( $mail->id == 'customer_invoice' ) {
               $mail->trigger( $this->order_id );
            }
         }
    }
  }

} //end class declaration


class CB_Bookings {
  public $count;
  public $bookings;


  public function __construct(){
    global $wpdb;
    $records = $wpdb->get_results("select id from wp_charter_bookings order by charter_date ASC");
    $bookings = array();
    foreach ($records as $record){
      $bookings[] = new CB_Booking($record->id);
    }
    $this->bookings = $bookings;
    $this->count = count($bookings);
  }

  public function sort_bookigns($field, $order = "DESC"){
      usort($this->bookings, function($a, $b) {
        return $a->field < $b->field ? 1 : -1;
      });
  }



}


?>
