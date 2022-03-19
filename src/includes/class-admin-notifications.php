<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;


/**
 * Send Captain reminders
 * CRON: run on wp_cron and send the charter scchedule to the captain each day. This feature does not run in the lite version. It is called from the activation hook when the wp_cron functions are set.
 * @var [type]
 */

add_action('cb_send_admin_reminders', __NAMESPACE__ . '\\cb_send_admin_reminders_function');

function cb_send_admin_reminders_function(){
  $message = new CB_Admin_Charter_Schedule();
  $message->send();
}

class CB_Admin_Notification{
  public $message;
  public $message_subject;
  public $captain;
  public $headers;

  public function __construct($subject, $message){
    $this->set_captain();
    $this->message = $message;
    $this->message_subject = $subject;
    $this->set_headers();
  }

  private function set_captain(){
    $this->captain = get_userdata(get_option('cb_captain'));
  }

  public function set_headers(){
    $headers = array();
    $headers[]='Content-Type: text/html; charset=UTF-8';
    $headers[]= 'Reply-To: '.$this->captain->data->display_name.' <'.$this->captain->data->user_email.'>';
    $headers[] = 'From: '.get_option('woocommerce_email_from_name').' <'.get_option('woocommerce_email_from_address').'>';
    $this->headers = $headers;
  }

  public function send(){
      wp_mail(
        $this->captain->data->user_email,
        $this->message_subject,
        $this->message,
        $this->headers
      );
  }

} //end class

class CB_Admin_Charter_Schedule {
  public $message;
  public $charters;
  public $customerdata;
  public $captain;
  public $headers;

  public function __construct(){
    $this->set_captain();
    $this->set_charters();
    $this->set_message();
    $this->set_message_subject();
    $this->set_headers();
  }

  private function set_captain(){
    $this->captain = get_userdata(get_option('cb_captain'));
  }

  private function set_message_subject(){
    $date = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $content = 'Upcoming Charter Schedule';
    $this->message_subject = $content;
  }

  public function set_headers(){
    $headers = array();
    $headers[]='Content-Type: text/html; charset=UTF-8';
    $headers[]= 'Reply-To: '.$this->captain->data->display_name.' <'.$this->captain->data->user_email.'>';
    $headers[] = 'From: '.get_option('woocommerce_email_from_name').' <'.get_option('woocommerce_email_from_address').'>';
    $this->headers = $headers;
  }

  public function send(){
    if($this->charters->charter_count > 0 ){
      wp_mail(
        $this->captain->data->user_email,
        $this->message_subject,
        $this->message,
        $this->headers
      );
    }
  }

  private function set_message(){
    $content = '<p style="font-size: 1.2rem; font-weight: bold; margin: 0">Charter Schedule (next 5 days)</p><p>The system will remind your guests to pay the balance due 3 days prior to boarding, but personal contact from you will help ensure the charter is confirmed, balance is paid, and keep the guests looking forward to thier trip. </p><p>Keep an eye on the weather, and communicate with your guests as thier charter approaches concerning the weather. This will reduce refunds and weather cancellations. </p>';
    foreach($this->charters->charters as $charter){
      $content .= $this->set_charter_message($charter);
    }
    $this->message = $content;
  }

  private function set_charter_message($charter){
    $date = new DateTime($charter->charter_datetime, new DateTimeZone(get_option('timezone_string')));
    $content = '<h3 style="margin-bottom: 0; margin-top: 20px; font-size: 1.1rem; font-weight: bold">'.$date->format('D, m-d-Y h:i a T').
    '</h3><strong>'.$charter->charter_name.'</strong>';
    $content .= $this->set_weather($charter->bookings[0]) ;
    $content.= '<span style="text-decoration: underline; font-style: italic">Groups:</span>';
    foreach($charter->bookings as $key => $booking){
      $content.= ($key == 0) ? $this->set_booking_message($booking, 'yes') : $this->set_booking_message($booking);
    }
    return $content;
  }

  private function set_booking_message($booking, $weather = 'no'){
    $date = new DateTime($booking->charter_date, new DateTimeZone(get_option('timezone_string')));
    //$content = ($weather == 'yes' ) ? $this->set_weather($booking) : '';
    //booking title is name and persons
    $content = '<div style="margin-left: 20px;"><p style="margin-top: 8px; margin-bottom: 0px !important">'.$booking->billing_name;
    $content .= ($booking->has_persons) ? ' ('.$booking->persons.' pp)' : ' (private)';
    $content .= '</p>';
    //contact
    $content .='<a href="tel:+1'.$booking->billing_phone.'">'.$booking->billing_phone.'</a> | <a href="mailto:'.$booking->billing_email.'">'.$booking->billing_email.'</a>';
    $content .= ($booking->booking_status == 'confirmed')
      ? '<br>Balance Paid'
      : '<br><span style="color: red">Balance Due: $'.$booking->final_balance.' on '.$booking->balance_due_date.'</span>';
    $content .= '</div>';
    return $content;
  }

  private function set_weather($booking){
    $forecast = new CB_OpenWeather($booking->location);
    $charter_weather = $forecast->get_weather_email_html($booking->charter_date, NULL);
    return $charter_weather;
  }



  private function set_charters(){
    $date = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $start = $date->format('Y-m-d');
    $end = $date->add(new DateInterval('P5D'))->format("Y-m-d");
    $charters = new CB_Charters($start, $end);
    $this->charters = $charters;
  }

  private function set_range(){
    $date = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));

  }

}// end class declaration


 ?>
