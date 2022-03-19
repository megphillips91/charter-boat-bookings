<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * Class for admin booking
 *
 * single booking view within the admin menu
 * -- CRUD on the booking from administration side of WP so that administrators can CRUD bookings outside of woocommerce transactions
*/

class CB_Admin_Booking {
  public $html;
  public $booking;

  public function __construct($booking_id){
    $this->booking = new CB_Booking($booking_id);
    $this->html = $this->wrap();
  }

  private function wrap(){
    $content = '<header class="modal-header"><h2 class="woocommerce-order-data__heading">Booking# '.$this->booking->id
    .': '.$this->booking->billing_name.'</h2> ';
    $content .= '<span class="cb-booking-subtitle">'.$this->booking->product_name.' '.$this->booking->date_object->format('m-d-Y').'</span>';
    $content .= '<button class="modal-close-link cb-close-booking dashicons dashicons-no-alt">
							</button></header>';
    $content .= '<div class="cb-booking-container" booking_id="'.$this->booking->id.'">';
    $content .= $this->billing_contact();
    $content .= $this->cruising_schedule();
    $content .= $this->booking_form();
    $content .= '</div>';
    return $content;
  }

  private function billing_contact(){
    $content = '<h3>Contact Information:</h3>'.$this->booking->billing_name.'<br>
    <a href="mailto:'.$this->booking->billing_email.'" target="_blank">'
    .$this->booking->billing_email.'</a>
    <br><a href="tel+1'.$this->booking->billing_phone.'">'
    .$this->booking->billing_phone.'</a>';
    $content .= '<hr>';
    return $content;
  }

  private function cruising_schedule(){
    $content = '<h3>Cruising Schedule:</h3>';
    $content .= 'Location: '.$this->booking->location;
    $content .= '<br>Date: '.$this->booking->date_object->format(('D, M j, Y H:i a T'));
    $content .= '<br>Duration: '.$this->booking->duration;
    return $content;
  }

  private function booking_form(){
    $starttime = new DateTime($this->booking->charter_date, new DateTimeZone(get_option('timezone_string')));
    $content = '<h3>Edit Charter Schedule</h3>';
    $content .= '<p>Editing the charter schedule below will automatically change the booking date/time and address the needs of the order for this booking. A coupon will be created and applied to the new order. Your customer will be emailed if there is any remaining balance. You will be notified if a refund is due to your customer.</p><p><span class="cb-strong-indent"><a class="button button-primary " href="https://msp-media.org/wordpress-plugins/charter-bookings/" booking_id="'.$this->booking->id.'" date="'.$this->booking->date_object->format('Y-m-d').'" target="_blank">Edit Schedule</a></span></p>';
    $content .= '<hr>';
    $content .= '<h3>Cancel Charter</h3><p>To cancel the charter, please go into each order and change the order status. Changing the order status will remove the booking from your calendar. Refunds are processed at the order level either through the WC Admin or directly with your merchant processor.</p>';
    $content .= '<span class="cb-strong-indent">Reservation Order#: <a target="_blank" href="'.$this->cb_get_order_admin_link($this->booking->orderid_reservation).'">'.$this->booking->orderid_reservation.'</a></span><br>';
    //cb_get_order_admin_link($this->booking->orderid_reservation)
    $content .= ($this->booking->orderid_balance != NULL) ? '<span class="cb-strong-indent">Balance Order#: <a target="_blank" href="'.$this->cb_get_order_admin_link($this->booking->orderid_balance).'">'.$this->booking->orderid_reservation.'</a>'.$this->booking->orderid_balance.'</a></span><hr>': '<hr>';
    return $content;
  }

  public function cb_get_order_admin_link($order_id){
    $content = admin_url( 'post.php?post='.$order_id.'&action=edit' );
    return $content;
  }

} //end class declaration

?>
