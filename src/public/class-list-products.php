<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

class CB_List_Products {
  public $date;
  public $products;
  public $html;


  public function __construct($date, $shownav = 'yes', $lazyload = 'yes', $action = NULL){
    $this->action = $action;
    $this->lazyload = $lazyload;
    $this->timezone = get_option('timezone_string');
    $this->date = $date;
    $this->set_dates();
    $this->shownav = $shownav;
    $this->set_products();
    $this->set_product_order_by();
    $this->set_html();
  }

  private function set_html(){
    $content = '<div class="hold-products-listing">'.$this->show_nav();
    $content .= '<div class="cb-list-products-container">';
    foreach($this->products as $product_id){
      if($this->lazyload == 'yes') {
        $product_title = get_the_title($product_id);
        $content .= '<div
          class="lazyload-product-listing cb-loading"
          product_id="'.$product_id.'"
          date="'.$this->date.'"
          list_action="'.$this->action.'">';
        $content .= '<h3><span class="fa-stack fa-lg">
      	<i class="fa fa-circle fa-stack-2x"></i>
      	<i class="fa fa-compass fa-inverse fa-spin fa-stack-2x"></i>
      </span> fetching availabilty of '.$product_title.'</h3>';
        $content .= '</div>';
      } else {
        $listing = new CB_List_Product($product_id, $this->date, NULL, $this->action);
        $content .= $listing->html;
      }
    }
    $content .= '</div></div>';
    $this->html = $content;
  }

  public function show_nav(){
    if($this->shownav == 'yes') {
      $content = '<div class="cb-list-products-headline"><h3>';
      $content .= ($this->dateobject >= $this->datetoday)
        ? '<a class="btn cb-list-products-yesterday"  date="'.$this->date.'" list_action="'.$this->action.'"><i class="fas fa-backward"></i></a>'
        : '<a class="btn cb-disabled"  date="'.$this->date.'" list_action="'.$this->action.'"><i class="fas fa-backward"></i></a>' ;
      $content .= date('l m-d-Y', strtotime($this->date)).'
      <a class="btn cb-list-products-tomorrow"  date="'.$this->date.'" list_action="'.$this->action.'"><i class="fas fa-forward"></i></a>';
      $content .= '</h3></div>';
      return $content;
    } else {}
    return '';
  }

  private function set_products(){
    $args = array(
    'type' => 'charter_booking',
    'return'=>'ids'
    );
    $products = wc_get_products( $args );
    $this->products = $products;
  }

  private function set_cb_meta($product_id){
    $booking_meta = get_charter_booking_meta($product_id);
    foreach($booking_meta as $key=>$meta){
      $this->$key = $meta;
    }
  }

  private function set_product_order_by(){
    $products = array();
    foreach($this->products as $product_id){
      $start_time = cb_get_product_starttime($product_id, $this->date);
      $products[$product_id]=$start_time->getTimestamp();
    }
    asort($products);
    $products_by_starttime = array();
    foreach($products as $key=>$value){
      $products_by_starttime[]=$key;
    }
    $this->products = $products_by_starttime;
  }

  private function set_dates(){
    $date = new DateTime($this->date, new DateTimeZone($this->timezone));
    $this->dateobject = $date;
    $datefivedays = new DateTime(NULL, new DateTimeZone($this->timezone));
    $this->datefivedays = $datefivedays;
    $datefivedays->add(new DateInterval('P5D'));
    $this->datetoday = new DateTime(NULL, new DateTimeZone($this->timezone));
  }

} // end class declaration

class CB_List_Product {
  public $html;
  public $date;
  public $dateobject;
  public $datefivedays;
  public $start_time;
  public $start_datetime;
  public $display_price;
  public $availability;
  public $display_weather;
  protected $product;

  public function __construct($product_id, $date = NULL, $shownav = NULL, $action=NULL){
    if($product_id == NULL){
      trigger_error("Must Provide product_id (int) to list a product", E_USER_ERROR);
    }
    $this->action = $action;
    $this->timezone = get_option('timezone_string');
    $this->product_id = $product_id;
    $this->date = ($date == NULL) ? date('Y-m-d') : $date ;
    $this->availability = new CB_Product_Availability($this->date, $this->product_id);
    $this->set_dates();
    $this->shownav = ($shownav == NULL) ? 'no' : $shownav;
    $product = wc_get_product($this->product_id);
    //$product->set_charter_booking_product_meta();
    $this->set_cb_meta();
    $this->set_start_time();
    $this->product = $product;
    $this->set_display_price();
    $this->set_weather();
    $this->draw();

  }

  private function draw(){
    $content = $this->show_nav();
    $content .= '<div class="cb-product-listing" product_id="'.$this->product_id.'" date="'.$this->date.'">';
    $content .= $this->get_details();
    $content .= $this->get_book_now();
    $content .= '</div>';
    $this->html = $content;
  }

  public function show_nav(){
    if($this->shownav == 'yes') {
      $content = '<div class="cb-product-listing-nav"><h3>';
      $content .= ($this->dateobject >= $this->datetoday)
        ? '<a class="btn cb-list-product-yesterday" product_id="'.$this->product_id.'" date="'.$this->date.'" list_action="'.$this->action.'"><i class="fas fa-backward"></i></a>'
        : '<a class="btn cb-disabled" product_id="'.$this->product_id.'" date="'.$this->date.'" list_action="'.$this->action.'"><i class="fas fa-backward"></i></a>' ;
      $content .= date('l m-d-Y', strtotime($this->date)).'
      <a class="btn cb-list-product-tomorrow" product_id="'.$this->product_id.'" date="'.$this->date.'"><i class="fas fa-forward"></i></a>';
      $content .= '</h3></div>';
      return $content;
    } else {}
    return '';
  }

  private function get_details(){
    $content = '<div class="cb-details-container">';
    $content .= '<a href="'.get_the_permalink($this->product_id).'"><h2>'.ucwords(get_the_title($this->product_id)).'</h2></a>';
    $content .= $this->get_fine_details();
    $content .= '</div>';
    return $content;
  }

  private function get_fine_details(){
    $content = '<div class="cb-fine-details-container">';
    $content .= $this->get_weather();
    //$content .= '<div class="weather-container"><p>'.$this->display_weather.'</p></div>';
    $content .= '<div><p class="subscript-size light">departure</p><p>'.$this->start_time.'</p></div>';
    $content .= '<div><p class="subscript-size light">duration</p><p>'.$this->_cb_duration.'</p></div>';
    $content .= '<div><p class="subscript-size light">type</p><p>';
    $content .= 'private';
    //$content .= ($this->availability->has_persons) ? 'shared': 'private';
    $content .= '</p></div>';
    $content .= '<div><p class="subscript-size light">';
    $content .= 'capacity';
    //$content .= ($this->availability->has_persons) ? 'available spots': 'capacity';
    $content .= '</p><p>';
    $content .= get_option('cb_booking_capacity');
    //$content .= $this->available_quantity();
    $content .=  '</p></div>';
    $content .= '</div>';
    return $content;
  }

  private function available_quantity(){
    if(!$this->availability->available){
      $content = "0";
      return $content;
    } else {
      $content = ($this->availability->has_persons)
        ? $this->availability->seats_available.' of '.($this->availability->capacity)
        : ($this->availability->capacity);
      return $content;
    }
  }

  private function get_weather(){

    if($this->dateobject < $this->datefivedays ){
      $content = '<div class="weather-container"><p>'.$this->display_weather.'</p></div>';
      return $content;
    } else {
      $content = '<div class="cb-weather-container">';
      $content .= '<p>'.$this->_cb_location.'</p><p class="subscript-size">forecast n/a';
      $content .= '</div>';
      return $content;
    }
  }

  private function get_book_now(){
    //$this->get_product_type($this->date).'-tocart
    $content = '<div class="cb-book-now-container">';
    $content .= ($this->availability->has_persons)
      ? '<p class="subscript-size">price per person</p>'
      : '';
    $content .= '<h3 class="price">'.$this->display_price.'</h3>';
    $content .= ($this->action == 'toorder' && $this->availability->has_persons)
      ? '<input type="number" id="quantity-'.$this->product_id.'" placeholder="persons" min="1" max="'.$this->availability->seats_available.'"/>'
      : '';
    $content .= '<button class="btn btn-lrg btn-danger ';
    $content .= ($this->action == NULL) ? ' cb-book-now ' : ' ' ;
    $content .= $this->get_product_type($this->date);
    //$content.= ($this->action != NULL) ? '-'.$this-action.' ': '-tocart ';
    $content .=  '" product_id="'.$this->product_id.'" date="'.$this->date.'">Book Now</button>';
    $content .= '</div>';
    if(!$this->availability->available){
      $content = '<div class="cb-book-now-container"><p class="subscript-size">not available</p>';
      $content .= '</div>';
    }
    return $content;
  }

  private function get_product_type($date){
    $calendar_date = new DateTime($date);
    $today = new DateTime();
    $interval = $calendar_date->diff($today)->days;
    $product_type = ($interval >= 3 && ($calendar_date->format('Y-m-d') >= $today->format('Y-m-d')))
      ? ' cb-reservation'
      : ' cb-fullcharter' ;
    $product_type .= ($this->action == NULL) ? '-tocart': '-'.$this->action ;
    $product_type .= ' ';
    return $product_type;
  }

  private function set_weather(){
    $weather = new CB_OpenWeather($this->_cb_location);
    $this->forecast = $weather->get_booking_forecast($this->start_datetime);
    $display = $weather->get_weather_widget_html($this->start_datetime, 'no');
    $this->display_weather = $display;
  }

  private function set_cb_meta(){
    $booking_meta = get_charter_booking_meta($this->product_id);
    foreach($booking_meta as $key=>$meta){
      $this->$key = $meta;
    }
  }

  private function set_start_time(){
    $start = cb_get_product_starttime($this->product_id, $this->date);
    $this->start_datetime = $start->format('Y-m-d H:i:s');
    $this->start_time = $start->format('g:i a');
  }

  private function set_display_price(){
    $price = $this->product->get_price();
    if(!strpos($price, '.')){
      $price = $price.'.00';
    }
    $this->display_price = '$'.$price;
  }

  private function set_dates(){
    $date = new DateTime($this->date, new DateTimeZone($this->timezone));
    $this->dateobject = $date;
    $datefivedays = new DateTime(NULL, new DateTimeZone($this->timezone));
    $this->datefivedays = $datefivedays;
    $datefivedays->add(new DateInterval('P5D'));
    $this->datetoday = new DateTime(NULL, new DateTimeZone($this->timezone));
  }

} // end class declaration

 ?>
