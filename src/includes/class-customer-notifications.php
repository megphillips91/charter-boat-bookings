<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;



/**
 * Send Customer reminders
 * CRON: run on wp_cron and send each customer with balance due an email beginning 5 days out from the charter. 
 * @var [type]
 */

add_action('cb_send_customer_reminders', __NAMESPACE__ . '\\cb_send_customer_reminders_function');

function cb_send_customer_reminders_function(){
  $reminders = new CB_Balance_Reminders(5);
  $reminders->send();
}

/**
 * [CB_Balance_Reminders description]
 *
 *  @param int days_advance_notice => number of days in advance the customers will begin to get balance due reminders.  EX: 5 would send the notification for all charters which occur from today through 5 days if the balance is still pending to be paid.
 *
 */

class CB_Balance_Reminders {
  public $bookings;

  public function __construct($days_advance_notice){
    $this->days_advance_notice = $days_advance_notice;
    $this->set_bookings();
  }

  private function set_bookings(){
    $start = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $end = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $end->add(new DateInterval('P'.$this->days_advance_notice.'D'));
    $args = array(
      'date_range'=>array(
        'start'=> $start->format('Y-m-d'),
        'end'=> $end->format('Y-m-d')
        ),
      'booking_status' => 'reserved'
    );
    $this->booking_args = $args;
    $bookings = new CB_Booking_Query($args, 'date_range');
    $this->booking_query = $bookings;
    $this->bookings = $bookings->bookings;
  }

  public function send(){
    $messages = array();
    foreach($this->bookings as $key=>$booking){
      if($booking->booking_status == 'confirmed'){
        unset($this->bookings[$key]);
      } else {
        $message = new CB_Balance_Reminder($booking->id);
        $message->send();
      }

    }
    //$this->messages = $messages;
  }

} //end class declaration

class CB_Balance_Reminder extends CB_Customer_Notification {
  public $message;
  public $booking;
  public $customerdata;
  public $captain;

  public function __construct($booking_id){
    $this->booking = new CB_Booking($booking_id);
    $this->set_customer();
    $this->set_captain();
    $this->set_headers();
    $this->message_subject = 'REMINDER: Confirm Your Upcoming Charter';
    $this->set_message();
  }

  protected function set_message(){
    $content = '<p>Hi '.$this->customer_firstname.', </p>';
    $content .= 'Hope you are excited for your upcoming charter.</p>';
    $content .= '<h3>'.$this->booking->product_name.'</h3><p>'.$this->booking->date_object->format('m-d-Y h:i a T') ;
    $content .= ($this->booking->has_persons == true) ? '<br>'.$this->booking->persons.' confirmed guests</p>': '<br>capacity of '.$this->booking->capacity.' people</p>';
    $content .= '<p>Your final charter balance is due. Please <a href="'.site_url().'/charter-confirmation/?booking_id='.$this->booking->id.'">click here</a> to pay the final balance. </p><p>Thank you,<br>Captain '.$this->captain->data->display_name.'</p>';
    $this->message = $content;
  }
  protected function set_message_subject(){
    $content = strtoupper($this->booking->product_name).': Balance Due';
    $this->message_subject = $content;
  }

} //end class declaration

class CB_Customer_Notification {
  public $message;
  public $booking;
  public $customerdata;
  public $captain;

  public function __construct($booking_id, $subject, $body){
    $this->booking = new CB_Booking($booking_id);
    $this->set_customer();
    $this->set_captain();
    $this->body = $body;
    $this->set_message();
    $this->message_subject = $subject;
    $this->set_headers();
  }

  protected function set_customer(){
    if($this->booking->user_id != NULL){
      $this->customerdata = get_userdata($this->booking->user_id);
    }
    $name_array = explode(' ', $this->booking->billing_name);
    $this->customer_firstname = $name_array[0];
  }

  protected function set_message(){
    $content = '<p>Hi '.$this->customer_firstname.', </p>';
    $content .= $this->body;
    $content .= '<p>Thank you,<br>Captain '.$this->captain->data->display_name.'</p>';
    $this->message = $content;
  }

  protected function set_captain(){
    $this->captain = get_userdata(get_option('cb_captain'));
  }

  protected function set_headers(){
    $headers = array();
    $headers[]='Content-Type: text/html; charset=UTF-8';
    $headers[]= 'Reply-To: '.$this->captain->data->display_name.' <'.$this->captain->data->user_email.'>';
    //$headers[] = 'Bcc: '.$this->captain->data->display_name.' <'.$this->captain->data->user_email.'>';
    $headers[] = 'From: '.get_option('woocommerce_email_from_name').' <'.get_option('woocommerce_email_from_address').'>';
    $this->headers = $headers;
  }

  public function send(){
    wp_mail(
      $this->booking->billing_email,
      $this->message_subject,
      $this->message,
      $this->headers
    );
  }
} //end class declaration

 ?>
