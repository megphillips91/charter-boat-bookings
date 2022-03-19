<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

function cb_admin_schedule($date = NULL){
  if ($date == NULL) {
    $date = date('Y-m-d');
  }
  $bookings = NULL;
  $schedule = new CB_Admin_Calendar('week', $date);
  return $schedule;
}

class CB_Admin_Calendar {
  public $today;
  public $sunday;
  public $otherdays;
  public $view;
  public $html;

  public function __construct($view, $start_date){
    $this->today = $start_date;
    $this->view = $view;
    $this->html = '<div class="cb-admin-bookings"><table class="wp-list-table widefat fixed striped posts">';
    $this->html .= $this->table_head();
    $this->html .= $this->table_body();
    $this->html .= '</table></div>';
  }

  private function table_head (){
    $content = '
    <thead>
    <tr>
      <th scope="col" id="day" class="manage-column column-day_head width-12">Date</th>
      <th scope="col" id="bookings" class="manage-column column-day_Bookings">Bookings</th>
    </tr>
    </thead>';
    return $content;
  }

  private function table_body () {
    $content = '<tbody id="the-list" class="week-view">';
    //$content .= $this->table_row(new DateTime('last sunday'));
    $content .= $this->loop_days();
    $content .= '</tbody>';
    return $content;
  }

  private function loop_days(){
    $content = '';
    $this->set_sunday();
    $date = new DateTime($this->sunday, new DateTimeZone($this->timezone));
    $date->sub(new DateInterval('P1D'));
    for ($x = 0; $x <= 28; $x++) {
      $date->add(new DateInterval('P1D'));
      $content .= $this->table_row($date->format('Y-m-d'));
    }
    return $content;
  }

  private function table_row($date){
    $date = new DateTime($date, new DateTimeZone($this->timezone));
    $content = '<tr class="hentry">';
    $content .= '<th scope="row" id="sunday" class=""><strong>'
    .$date->format('D')
    .'</strong><br>'
    .$date->format('m/d')
    .'</th>';
    $this->bookigns = array();
    $content .= $this->get_bookings($date->format('Y-m-d'));
    $content .= '</tr>';
    return $content;
  }

  private function get_bookings($date){
    $content = '';
    $args = array(
      'charter_date'=>$date,
      'booking_status'=>array('reserved', 'confirmed')
    );
    $bookings = new CB_Booking_Query($args, 'date');
    $this->bookings[] = $bookings;
    $content .= '<td><div class="hold-bookings">';
    foreach($bookings->bookings as $booking){
      $end = new DateTime($booking->end_time, new DateTimeZone($this->timezone));
      $content .= '<!--booking-'.$booking->id.'-->';
      $content .= '<div class="admin-booking booking-'.$booking->id;
      $content .= ($booking->booking_status == 'confirmed') ? ' confirmed ">' : ' reserved ">';
      $content .= '<strong><a class="cb-open-booking" booking="'.$booking->id.'">'.$booking->product_name.'</a></strong><br>'.$booking->billing_name;
      $content .= '<br>'.$booking->location;
      $content .= ($booking->has_persons) ? ' (x'.$booking->persons.')' : ' ';
      $content  .= '<br>'.$booking->date_object->format("g:i a").' - '.$end->format("g:i a").'<br>';
      $content .= '</div>';
    }
    $content .= '</div></td>';
    $this->bookings_content = $content;
    return $content;
  }


/**
 * get the date string of last sunday
 * @param string date of the day you want to find the last Sunday of.
 * @return string "Y-m-d"
 */
  private function set_sunday(){
    $timezone = get_option('timezone_string');
    $this->timezone = $timezone;
    $today = new DateTime($this->today, new DateTimeZone($timezone));
    $this->today = $today->format('Y-m-d');
    $today_N = $today->format('N');
    $lastMonday = $today->sub(new DateInterval('P'.($today_N -1).'D'));
    $lastSunday = $lastMonday->sub(new DateInterval('P1D'));
    $this->sunday = $lastSunday->format('Y-m-d');
  }

}


 ?>
