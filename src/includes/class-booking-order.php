<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * CB Get Order Type
 * pass in a date for the charter and return whether the customer should pay full charter fee or reservation
 *
 * @param  [type] $date [description]
 * @return [type]       [description]
 */

function cb_get_type($date){
  $date = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
  $rez_cutoff = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
  $rez_cutoff->add(new DateInterval('P3D'));
  if($date > $rez_cutoff){
    return 'reservation';
  } else {
    return 'fullcharter';
  }
}

/**
 *  CB Order items
 *
 * follows through on WC Order to create a simplified more accessible object of bookings items.
 * -- useful for looping through booking items within the WC order leve hooks
 * -- useful for looping through reservations and confirmations separately.
 *
 * @param int (required) WC Order ID
 *
 */
class CB_Order_Items {
  public $count;
  public $order_id;
  public $bookings;
  public $reservations;
  public $confirmations;
  private $items;

  public function __construct($order_id){
    $order = wc_get_order($order_id);
    //$this->set_address_array();
    $this->order_id = $order_id;
    $this->items = ($order)? $order->get_items() : NULL;
    $this->count = count($this->items);
    $this->bookings = array();
    $this->set_reservations();
    $this->set_confirmations();

  }

  private function set_reservations(){
    $this->reservations = array();
    if($this->items != NULL){
      foreach ($this->items as $key=>$item){
        $data = $item->get_data();
        if(cb_item_is_reservation($data['variation_id'])
          || cb_item_is_full_charter($data['variation_id'])){
          //add to bookings;
          $factory = new CB_Booking_Factory();
          $booking = $factory->make_booking('reservation', $data['variation_id'], $this->order_id);
          $booking->set_order_item_id($key);
          $booking->set_persons($item->get_quantity());
          $this->bookings[$booking->id] = $booking;
          $this->reservations[$booking->id]= $booking;
          if(cb_item_is_full_charter($data['variation_id'])){
            $booking->set_booking_status('confirmed');
          } else {
            $booking->set_booking_status('reserved');
          }
        }
      }
    }
  }

  private function set_confirmations(){
    $this->confirmations = array();
    if($this->items != NULL){
      foreach ($this->items as $key=>$item){
        $data = $item->get_data();
        if(cb_item_is_final_balance($data['variation_id'])){
          $factory = new CB_Booking_Factory();
          $booking = $factory->make_booking('finalbalance', $data['variation_id'], $this->order_id);
          $booking->set_order_item_id($key);
          $booking->set_persons($item->get_quantity());
          $this->bookings[$booking->id]=$booking;
          $this->confirmations[$booking->id]= $booking;
        }
      }
    }
  }

  /**
   * Remove Product from Order
   * @param  int $product_id not the variation - the product id
   */
  public function remove_product($product_id, $variation_id){
    $order_id = $this->order_id;
    $order = wc_get_order($order_id);
    $order->update_status( 'on-hold' );
    foreach($this->items as $key=> $item){
      $data = $item->get_data();
      if($data['product_id'] == $product_id){
        wc_delete_order_item($key);
        wc_update_product_stock( $variation_id, $data['quantity'], 'increase' );
      }
    }
  }

  /**
   * Update order with booking schedule changes
   * @param $product_id of the parent product you are changing to (the new one not the old one)
   * @param $date (Y-m-d)
   * @return $variation_id of the date of the sail
   *
   */
  public function add_product($product_id, $quantity, $date, $type){
    $order_id = $this->order_id;
    //creates the variation for the new date
    $variation_id = cb_variation($product_id, $date, $type);
    //handle order revision
    $order = wc_get_order($order_id);
    if (!empty($order)) {
      $order->update_status( 'on-hold' );
      $item_id = $order->add_product(wc_get_product($variation_id), $quantity);
      wc_update_product_stock( $variation_id, $quantity, 'decrease' );
      $booking_meta = get_charter_booking_meta($product_id);
      $start_time = cb_get_product_starttime($product_id, $date);
      $item_meta    = array(
        'Location' =>$booking_meta['_cb_location'],
        'Date' => $start_time->format('l, m-d-Y'),
        'Start Time' =>$start_time->format('g:i a T'),
        'Duration' =>$booking_meta['_cb_duration'].' hours'
      );
      foreach($item_meta as $meta_key => $meta_value){
        wc_add_order_item_meta( $item_id, $meta_key, $meta_value );
      }
      return $variation_id;
  }
}

}//end class declaration


 ?>
