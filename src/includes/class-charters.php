<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * CB Charters
 *
 * Basically, there is a need to speak with the captains in terms of charters vs. bookings. When and where do they need to show up and who will be on the boat at that time. Because of per-person charters, there can be multiple charters  This class is built off of a looped boooking query which will aggregate the bookings into charter times.
 *
 * @param string start - Y-m-d date string - the start of the date range that you want to query for Charters
 * @param string End - Y-m-d date string - the end of the date range that you want to query for Charters
 */

class CB_Charters {
    public $start;
    public $end;
    public $charter_times;
    public $charters;
    public $charter_count;
    //private $bookings;

    public function __construct($start, $end){
      if(!isset($start) || !isset($end)){
        trigger_error("Must provide a start and end for the date range you'd like to pull charters", E_USER_ERROR);
      }
      $this->start = $start;
      $this->end = $end;
      $this->set_charter_datetimes();
      $this->set_charters();
    }

    private function set_charters(){
      $charters = array();
      foreach($this->charter_times as $datetime){
        $charters[] = new CB_Charter($datetime);
      }
      $this->charters = $charters;
      $this->charter_count = count($charters);
    }

    private function set_charter_datetimes(){
      $args = array(
        'date_range'=>array('start'=>$this->start, 'end'=>$this->end),
        'sort'=>'ASC'
      );
      $bookings = new CB_Booking_Query($args, 'date_range');
      $datetimes = array();
      foreach($bookings->bookings as $booking){
        $datetimes[]=$booking->charter_date;
      }
      $this->charter_times = array_unique($datetimes);
    }

} //end class declaration

class CB_Charter{
  public $charter_datetime;
  public $charter_name;
  public $number_bookings;
  public $bookings;

  public function __construct($datetime){
    $this->charter_datetime = $datetime;
    $this->set_bookings();
    $this->charter_name = $this->bookings[0]->product_name;
    $this->number_bookings = count($this->bookings);
  }

  private function set_bookings(){
    $args = array(
      'datetime'=>$this->charter_datetime
    );
    $bookings = new CB_Booking_Query($args, 'datetime');
    $this->bookings = $bookings->bookings;
  }

}// end class declaration

?>
