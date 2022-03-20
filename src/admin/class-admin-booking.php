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
    $content = '<header class="modal-header">';
    $content .= '<div class="cb-admin-flex">';
    $content .= '<div><h2 class="woocommerce-order-data__heading">Booking# '.$this->booking->id
    .': '.$this->booking->billing_name.'</h2> ';
    $content .= '<span class="cb-booking-subtitle">'.$this->booking->product_name.' '.$this->booking->date_object->format('m-d-Y').'</span></div>';
    $content .= '<div><mark class="order-status status-'.$this->booking->booking_status.'">
            <span>'.$this->booking->booking_status.'</span></mark></div>';
    $content .= '</div>';
    $content .= '<button class="modal-close-link cb-close-booking dashicons dashicons-no-alt">
							</button>';
    $content .= '</header>'; //end header
    $content .= '<div class="cb-booking-container" booking_id="'.$this->booking->id.'">';
    $content .= $this->interior();
    $content .= '</div>';
    return $content;
  }

  private function interior(){
    $content = '<div class="cb-admin-flex">'.$this->billing_contact();
    $content .= $this->booking_orders();
    $content .= $this->cruising_schedule();

    if($this->booking->booking_status != 'cancelled'){
      //$content .= '<hr><h3>Booking Actions</h3>';
      $content .= '<div class="cb-admin-booking-actions"><h3>Booking Actions:</h3>';
      //$content .= $this->edit_charter();
      $content .= $this->cancel_charter();
      $content .= $this->confirm_charter();
      $content .= '</div>';
    }
    $content .= '</div>';
    return $content;
  }

  private function booking_orders(){
    global $woocommerce;
    $content = '<div><h3>Orders</h3>';
    if($this->booking->orderid_reservation !== null){
      $rez_order = wc_get_order($this->booking->orderid_reservation);
      $content .= '<p>Order #<a href="'.$this->cb_get_order_admin_link($this->booking->orderid_reservation).'">'.$this->booking->orderid_reservation.'</a> for '.get_woocommerce_currency_symbol().$rez_order->get_total().' '.$rez_order->get_status().'</p>';
    }
    if($this->booking->orderid_balance !== null){
      $balance_order = wc_get_order($this->booking->orderid_balance);
      $content .= '<p>Order #<a href="'.$this->cb_get_order_admin_link($this->booking->orderid_balance).'">'.$this->booking->orderid_balance.'</a> for '.get_woocommerce_currency_symbol().$balance_order->get_total().' '.$balance_order->get_status().'</p>';    
    }
    $content .= '</div>';
    return $content;
  }

  private function cb_get_order_admin_link($order_id){
    $content = admin_url( 'post.php?post='.$order_id.'&action=edit' );
    return $content;
  }

  private function billing_contact(){
    /*MEGTODO this only works for US clients. need to internationalize the phone setup*/
    $tel_link = str_replace('(', '', $this->booking->billing_phone);
    $tel_link = str_replace('-', '', $tel_link);
    $tel_link = 'tel:+1'.$tel_link;
    $content = '<div><h3>Contact Information:</h3>'.$this->booking->billing_name.'<br>
    <a href="mailto:'.$this->booking->billing_email.'" target="_blank">'
    .$this->booking->billing_email.'</a>
    <br><a href="'.$tel_link.'">'
    .$this->booking->billing_phone.'</a></div>';
    return $content;
  }

  private function cruising_schedule(){
    $content = '<div><h3>Cruising Schedule:</h3>';
    $content .= 'Location: '.$this->booking->location;
    $content .= '<br>Date: '.$this->booking->date_object->format(('D, M j, Y H:i a T'));
    $content .= '<br>Duration: '.$this->booking->duration.'</div>';
    return $content;
  }

  private function edit_charter(){
    return '<p><a class="button button-primary cb-edit-booking" booking_id="'.$this->booking->id.'" date="'.$this->booking->date_object->format('Y-m-d').'">Edit</a></p>';
  }

  private function cancel_charter(){
      return '<p><a class="button button-primary cb-cancel-booking" booking_id="'.$this->booking->id.'" date="'.$this->booking->date_object->format('Y-m-d').'">Cancel</a></p>';
  }

  private function confirm_charter(){
    if($this->booking->booking_status == 'reserved' || $this->booking->booking_status == 'on-hold'){
      return '<p><a class="button button-primary cb-admin-confirm-booking" booking_id="'.$this->booking->id.'" >Confirm</a></p>';
    }
  }
  
} //end class declaration

?>
