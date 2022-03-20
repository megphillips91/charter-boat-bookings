<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * CB_Booking_Query
 *
 * WPDB query against fields of the bookings table.
 *
 * @param array $args = associative array;
 * fails if bad array keys or values are passed into the query.
 * @param string $args['charter_date] specify charter_date as YYYY-MM-DD with no time specified
 * @return array an array of booking objects
 */

class CB_Booking_Query {
  public $query;
  public $bookings;
  public $ids;
  public $args;

  public function __construct($args, $type = NULL){
    global $wpdb;
    if($type == NULL){trigger_error("must provide query type. Options are date, date_range, simple, datetime", E_USER_ERROR);}
    $query_name = $type.'_query';
    $this->args = $args;
    $this->$query_name();
    $results = $wpdb->get_results($this->query);
    $this->ids = cb_wp_collapse($results, 'id');
    $this->bookings = array();
    foreach($this->ids as $id){
      $this->bookings[] = new CB_Booking($id);
    }

  }

  private function datetime_query(){
    global $wpdb;
    $qry = "SELECT id FROM ".$wpdb->prefix."charter_bookings  ";
    $qry .= "WHERE charter_date = '".$this->args['datetime']."' ";
    if(count($this->args) >= 2){
        $x=0;
      foreach($this->args as $key=>$value){
        if($key != 'datetime' && $key != 'sort'){
          $x++;
          $qry .=  " AND " ;
          $qry .= " ".$key." = '".$value."'";
        }
      }
    }
    $this->query = $qry;
    return $qry;
  }

  private function simple_query(){
    global $wpdb;
    $qry = "SELECT id FROM ".$wpdb->prefix."charter_bookings  ";
    if(count($this->args) >= 1){
    $qry .= '  WHERE';
    $x=0;
    foreach($this->args as $key=>$value){
      $x++;
      $qry .= ($x == 1) ? "  " : " AND " ;
      $qry .= " ".$key." = '".$value."'";
    }
    }
    $qry .= " ORDER BY charter_date DESC";
    $this->query = $qry;
    return $qry;
  }

  private function date_query(){
    global $wpdb;
    if(isset($this->args['booking_status']) && is_array($this->args['booking_status'])){
      $status_array = $this->args['booking_status'];
      unset($this->args['booking_status']);
    }
    $qry = "select a.id from ".$wpdb->prefix."charter_bookings a
      JOIN (SELECT date(charter_date) as the_date, id from ".$wpdb->prefix."charter_bookings) b
      on a.id=b.id
      where b.the_date = '".$this->args['charter_date']."' ";
      if(isset($status_array)){
        $qry .= ' and ( ';
        $count = count($status_array);
        foreach($status_array as $key=>$status){

          $qry .= "booking_status = '".$status."' ";
          if($key != $count-1){
            $qry .= " || ";
          }
        }
        	//booking_status = 'reserved' || booking_status = 'confirmed'
        $qry .= ") ";
      }
    if(count($this->args) >= 2){
        $x=0;
      foreach($this->args as $key=>$value){
        if($key != 'charter_date'){
          $x++;
          $qry .=  " AND " ;
          $qry .= " ".$key." = '".$value."'";
        }
      }
    }

    $qry .= " ORDER BY charter_date ASC";
    $this->query = $qry;
    return $qry;
  }



  private function date_range_query(){
    global $wpdb;
    $sort = (!isset($this->args['sort'])) ? 'ASC' : $this->args['sort'];
    $qry = "select * from ".$wpdb->prefix."charter_bookings
where date(charter_date) ";
    if(is_array($this->args['date_range'])){
      $qry .= ">= '".$this->args['date_range']['start']."'
&& date(charter_date) <= '".$this->args['date_range']['end']."' ";
    } else {
      if($this->args['date_range'] == 'future'){
        $qry .= " >= date(now()) ";
      }
      if($this->args['date_range'] == 'past'){
        $qry .= " <= now() ";
      }
    }
      if(count($this->args) >= 2){
          $x=0;
        foreach($this->args as $key=>$value){
          if($key != 'date_range' && $key != 'sort'){
            $x++;
            $qry .=  " AND " ;
            $qry .= " ".$key." = '".$value."'";
          }
        }
      }
    $qry .= " ORDER BY charter_date ".$sort;
    $this->query = $qry;
  }

} // end class declaration CB_Booking_Query



 ?>
