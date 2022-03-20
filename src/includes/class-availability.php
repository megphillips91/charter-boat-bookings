<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * Find the next available date for a given product
 *
 * provide a start date if you need the next available after any given unavailable date.
 *
 * @param  int $product_id woocommerce product id
 * @param string StartDate (Y-m-d)
 * @return string  Date (Y-m-d)
 */
function cb_find_next_available($product_id){
  $array = array();
  $date = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
  do{
    $date->add(new DateInterval("P1D"));
    $availability = new CB_Product_Availability($date->format('Y-m-d'), $product_id);
    //echo $date->format('Y-m-d').' '.$availability->available.'<br>';
    $array[]=$date->format('Y-m-d');
    } while ($availability->available);
  return $array[0];
}

/**
 * Global availability
 *
 * @param string Y-m-d
 */
class CB_Global_Availability {
  public $date;
  public $available;
  private $cb_products;

  public function __construct($date){
    $this->date = (isset($date)) ? $date : date('Y-m-d');
    $this->set_products();
    $this->set_availability();
  }

  private function set_products(){
    $args = array(
    'type' => 'charter_booking',
    'return'=>'ids'
    );
    $products = wc_get_products( $args );
    $this->cb_products = $products;
  }

  private function set_availability(){
    $this->available = false;
    foreach($this->cb_products as $product_id){
      $availability = new CB_Product_Availability($this->date, $product_id);
      if($availability->available == true){
        $this->available = true;
        break;
      }
    }

  }

} //end class declaration






/**
 * CB Product Availabilty
  *
  * provide the product id and the date you are interested in, and this calss returns the availability of that product.
  *
  * BIG PRINCIPLE to remember within this plugin is that the full charter and reservation work together as one with charters that have_persons. The stock qty of the reservation product is no longer valid once a full charter variation has been created. Therein, if the full charter has been created, seats available = stock_qty of the full charter variation.
  *
  * TODO add admin global capacity setting; add same day bookign window setting
  *
  * TODO write in functionality which checks against admin blackouts which would not neccesarily adjust stock qty.
  *
  *
  * @param int product_id of the parent product. Do Not pass in the date variation.
  * @param string date Y-m-d H:i:s
  *
  * @property bool available
  * @property bool has persons - true if available per person; false if private charter
  * @property int if has_persons, then returns seats available. otherwise, returns global private charter capacity
  */

class CB_Product_Availability {
  public $date;
  public $product_id;
  public $available;
  public $has_persons;
  public $capacity;
  public $open_today;
  public $stock_status;
  public $seats_available;
  public $within_blackout;
  public $bookings_conflict;
  protected $variations;
  protected $bookings_today;

  public function __construct($date, $product_id){
    $this->date = $date;
    $this->product_id = $product_id;
    $starttime = cb_get_product_starttime($this->product_id, $date);
    $this->starttime = $starttime;
    $this->has_persons = $this->set_has_persons();
    $this->set_charter_office_is_open();
    $this->set_capacity();
    $this->set_variations();
    $this->set_seats_available();
    $this->set_stock_status();
    $this->set_bookings_today();
    $this->set_bookings_conflict();
    $this->set_outside_window();
    $this->set_within_booking_window();
    $this->set_within_blackout();
    $this->set_availability();
  }

  private function set_has_persons(){
    $product = wc_get_product($this->product_id);
    if($product->get_sold_individually() == true){
      return false;
    } else {
      return true; }
  }

  private function set_capacity(){
    $product = wc_get_product($this->product_id);
    $stock_qty = $product->get_stock_quantity();
    if($stock_qty == NULL){
      $this->capacity = get_option('cb_booking_capacity');
    } else {
      $this->capacity = $stock_qty;
    }
  }

  private function set_availability(){
    if($this->outside_window){
      $this->available = false;
      return false;
    }
    if(!$this->open_today){
      $this->available = false;
      return false;
    }
    if($this->within_blackout == true){
      $this->available = false;
      return false;
    }
    if(!$this->within_booking_window){
      $this->available = false;
      return false;
    }
    if($this->stock_status == 'instock'){
      $this->available = true;
    } else {
      $this->available = false;
    }
    if($this->bookings_conflict){
      $this->available = false;
    }
    //now check for global days of the week
    //now check for global blackouts
    //now check for product days of the week
  }

  private function set_charter_office_is_open(){
    $this->open_today = true;
  }

  private function set_within_blackout(){
    $blackouts = new CB_Blackouts();
    $date = new DateTime($this->date, new DateTimeZone(get_option('timezone_string')));
    foreach($blackouts->blackouts as $blackout){
      $start = new DateTime($blackout['start'], new DateTimeZone(get_option('timezone_string')));
      $end = new DateTime($blackout['end'], new DateTimeZone(get_option('timezone_string')));
      if($start <= $date && $date <= $end){
        $this->within_blackout = true;
        return true;
      }
    }
    $this->within_blackout = false;
  }

  private function set_outside_window(){
    $weeks = get_option('cb_weeks_advance');
    $date = new DateTime($this->date, new DateTimeZone(get_option('timezone_string')));
    $window =  new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $window->add(new DateInterval('P'.$weeks.'W'));
    if($date > $window){
      $this->outside_window = true;
    } else {
      $this->outside_window = false;
    }
  }


  /**
   * set reservations available
   *
   * function checks the variation for reservations. If it exists, returns the remaining stock qty. if not, then it returns NULL.
   * The idea behind this is that even if the reservation product variation is not created, or had remaining seats, that does not neccesarily mean that there are actual seats available. We also have to check if full charter has been created and booked. If so, then that would be the actual seats available....
   *
   * @return int | NULL
   */
  private function set_seats_available(){
    if(isset($this->variations['full_charter'])){
      $fullcharter = wc_get_product($this->variations['full_charter']);
      $this->seats_available = $fullcharter->get_stock_quantity();
    }
    if(isset($this->variations['reservation'])){
      $reservations = wc_get_product($this->variations['reservation']);
      $this->seats_available = $reservations->get_stock_quantity();
    }
    if(!isset($this->variations['full_charter']) && !isset($this->variations['reservation'])){
      $this->seats_available = $this->capacity;
    }
  }

  /* sets stock status based on seats available */
  private function set_stock_status(){
    if($this->seats_available > 0){
      $this->stock_status = 'instock';
    } else {
      $this->stock_status = 'outofstock';
    }
  }



  /**
   * get today's variations
   *
   * pass in a date and a parent product id and get all the variation IDs for that date. remember there will possibly be a reservation, full charter, and balance to handle for this one date/one product
   *
   * @return bool | array if any of the variations have ever been created, then return an array of variation_ids for that date
   */
  private function set_variations(){
    $variations = cb_variations_exist($this->product_id, $this->date);
    $return_array = array();
    if($variations){
      foreach ($variations as $variation){
        if(strpos($variation->post_name, 'fullcharter')){
          $return_array['full_charter'] = $variation->ID;
        }
        if(strpos($variation->post_name, 'reservation')){
          $return_array['reservation'] = $variation->ID;
        }
        if(strpos($variation->post_name, 'confirmation')){
          $return_array['confirmation'] = $variation->ID;
        }
      }
    }
    $this->variations = $return_array;
  }

  /**
   * Bookings today
   *
   * sets the property bookings today. basically calls the booking query object
   *
   */
  private function set_bookings_today(){
    $date = date('Y-m-d', strtotime($this->date));
    $args = array(
      'charter_date'=>$date
    );
    $bookings = new CB_Booking_Query($args, 'date');
    $this->bookings_today = $bookings->bookings;
  }

  private function set_bookings_conflict(){
    if(count($this->bookings_today) < 1 ){
      $this->bookings_conflict = false;
    }
    foreach($this->bookings_today as $booking){
      if($this->has_conflict($booking) == true){
        if($this->has_persons == true
          && $booking->has_persons == true
          && $booking->product_id == $this->product_id){
          $this->bookings_conflict = false;
        } else {
          $this->bookings_conflict = true;
        }
      }
    }
  }

  private function set_within_booking_window(){
    $window = new DateTime();
    $window->setTimezone(new DateTimeZone(get_option('timezone_string')));
    $buffer = get_option("cb_same_day_buffer");
    $window->add(new DateInterval("PT".$buffer."M"));
    $this->window = $window;
    if($this->starttime < $window){
      $this->within_booking_window = false;
    } else {
      $this->within_booking_window = true;
    }
  }

  /**
   * has_conflict
   * checks if the date on the calendar has bookings which conflict over the same time as the product passed in
   * @param  object $booking the booking object of the booking we are testing
   * @param  int $product_id the woocommerce product_id
   * @param  string $date
   * @return  bool  true if the booking conflicts | false if no conflict
   */

  private function has_conflict($booking){
    $product_id = $this->product_id;
    $date = $this->date;
    $product_meta = get_charter_booking_meta($product_id);
    $product_starttime = cb_get_product_starttime($product_id, $date);
    //product end time
    $timezone = get_option('timezone_string');
    $product_endtime = cb_calc_end_time($product_starttime->format("Y-m-d H:i:s"), $product_meta['_cb_duration']);
    $product_endtime = new DateTime($date.' '.$product_endtime, new DateTimeZone($timezone));

    //product start time
    $product_starttime = cb_get_product_starttime($product_id, $date);
    //echo '<pre>PST: '; var_dump($product_starttime); echo '</pre>';
    $booking_starttime = $booking->date_object;
    //echo '<pre>BST: '; var_dump($booking_starttime); echo '</pre>';
    $booking_endtime = new DateTime($booking->end_time, new DateTimeZone($timezone));

    $conflicts = "";
    //the product ends within the booking window
    if($booking_endtime >= $product_starttime
      //the booking ends after the product starts
      && $booking_endtime <= $product_endtime)
      //the booking ends before the product ends
      {
        return true;
      }
    //the product starts within the booking window
    if($booking_starttime >= $product_starttime
      //the booking starts after the product starts
      && $booking_starttime <= $product_endtime)
      //the booking starts before the product ends
      {
        return true;
      }
    //the booking ranges over the entire product
      if($booking_starttime <= $product_starttime
        //the booking starts before product starts
        && $booking_endtime >= $product_endtime)
      {
        return true;
      }
    //the product ranges over the entire booking
      if($booking_starttime >= $product_starttime
        //the booking starts after product starts
        && $booking_endtime <= $product_endtime)
        //the booking ends before the product ends
      {
        return true;
      }

  } //end has_conflict method


} //end class declaration




/* =============== DEPRECATED ================
 CODE PULLED FROM CALENDAR CLASS
 =========================================== */


/*
  private function today_is_available($date){
    $date = new dateTime($date);
    $today = new DateTime();
    if($date->format('Y-m-d') < $today->format('Y-m-d')){ return FALSE; }
    $args = array(
      'location'=>$this->location,
      'charter_date'=>$date->format("Y-m-d")
    );
    $bookings_today = new CB_Booking_Query($args);
    foreach($bookings_today->bookings as $booking){
      $has_conflict = $this->has_conflict($booking, $this->product_id, $date->format("Y-m-d"));
      if($has_conflict){
        $product = wc_get_product($this->product_id);
        if($product->get_sold_individually() == true){
          return FALSE;
        } else {
            $stock_status = $this->get_stock_status($date->format("Y-m-d"));
            if($stock_status == 'instock') {
              return TRUE;
            } else {
              return FALSE;}
        }
      }
    }
  }
*/


  /**
  * get stock status
  * what is stock status of this product on this date?
  * @param  string  $date 'Y-m-d'
  * @return boolean
  */
 /*
  private function get_stock_status($date){
  //do any reservation or full charter variations exist? if no, then return true
  $variations = cb_variations_exist($this->product_id, $date);
  if($variations) {
    foreach ($variations as $variation){
      if(strpos($variation->post_name, 'fullcharter')){
        $fullcharter = wc_get_product($variation->ID);
        $stock_status = $fullcharter->get_stock_status();
        return $stock_status;
      }
      if(strpos($variation->post_name, 'reservation')){
        $reservation = wc_get_product($variation->ID);
        $stock_status = $reservation->get_stock_status();
        return $stock_status;
      }
    }
  } else {
    $stock_status = 'instock';
    return $stock_status;
  }
  }
*/






?>
