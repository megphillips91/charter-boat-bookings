<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;


class CB_Sunset_Times {
  public $timezone;
  public $locations;
  public $max_known_sunset;
  public $last_needed_sunset;
  public $days_advance_booking;
  public $number_sunsets_needed;
  public $sunset_times;
  public $max_number_to_fetch;


  public function __construct($max_number_to_fetch = NULL){
    $this->max_number_to_fetch = $max_number_to_fetch;
    $this->timezone = get_option('timezone_string');
    $weeks_advance = get_option('cb_weeks_advance');
    $this->days_advance_booking = $weeks_advance * 7;
    $this->set_locations();
    $this->set_max_known_sunset();
    $this->set_last_needed_sunset();
    $this->set_number_sunsets_needed();
  }

  public function commit_sunset_times(){
    global $wpdb;
    foreach($this->sunset_times as $sunset){
      $wpdb->insert(
        'wp_cb_sunset_times',
        $sunset
      );
    }
    return true;
  }

  public function fetch_sunset_times(){
    $sunset_times = array();
    if($this->number_sunsets_needed > $this->max_number_to_fetch && $this->max_number_to_fetch != NULL) {
      $this->max_number_to_fetch = $this->max_number_to_fetch;
    } else {
      $this->max_number_to_fetch = $this->number_sunsets_needed;
    }
    foreach($this->locations as $location){
      for ($x = 0; $x <= $this->max_number_to_fetch; $x++){
        $date = new DateTime($this->max_known_sunset->format('y-m-d'), new DateTimeZone($this->timezone));
        if($x != 0){$date->add(new DateInterval('P'.$x.'D'));}
        $sunset = new CB_Sunset_Time($location, $date->format('Y-m-d'));
        $sunset_times[]= array(
          'twilight'=>$sunset->twilight_mysql,
          'sunset'=>$sunset->sunset_mysql,
          'location_name'=>$location,
        );
      }
  }
    $this->sunset_times = $sunset_times;
  }

  private function set_locations(){
    $locations = new CB_Locations;
    $this->locations = array();
    $this->number_locations = $locations->number;
    foreach($locations->locations as $location){
      $this->locations[] = $location->name;
    }
  }

  private function set_max_known_sunset(){
    global $wpdb;
    $max_sunset = $wpdb->get_row("select max(sunset) as 'max_sunset' from wp_cb_sunset_times a");
    $this->max_known_sunset = new DateTime($max_sunset->max_sunset, new DateTimeZone($this->timezone));
  }

  private function set_last_needed_sunset(){
    $last_sunset_date = new DateTime();
    $last_sunset_date->setTimezone(new DateTimeZone($this->timezone));
    $last_sunset_date->add(new DateInterval('P'.$this->days_advance_booking.'D'));
    $this->last_needed_sunset = $last_sunset_date;
  }

  private function set_number_sunsets_needed(){
    $days_diff = $this->days_diff($this->last_needed_sunset, $this->max_known_sunset);
    $this->number_sunsets_needed = $days_diff;
  }

  private function days_diff($d1, $d2) {
    $x1 = $this->days($d1);
    $x2 = $this->days($d2);

    if ($x1 && $x2) {
        return abs($x1 - $x2);
    }
  }

  private function days($x) {
    if (get_class($x) != 'DateTime') {
        return false;
    }
    $y = $x->format('Y') - 1;
    $days = $y * 365;
    $z = (int)($y / 4);
    $days += $z;
    $z = (int)($y / 100);
    $days -= $z;
    $z = (int)($y / 400);
    $days += $z;
    $days += $x->format('z');
    return $days;
  }

}// end class declaration


/**
 * provides sunset time using API --> https://sunrise-sunset.org/api
 * uses google geocoding API to get the lat lng from the address
 * @param string location nickname from woocommerce product settings.
 * @param string date(Y-m-d)
 *
 */

class CB_Sunset_Time {
  public $location;
  public $sunset;
  public $twilight;
  public $sunset_mysql;
  public $twilight_mysql;
  public $date;
  public $address;
  public $params;
  public $location_name;
  public $timezone;
  public $lat;
  public $lng;


  public function __construct($location_nickname, $date){
    $this->location_name = $location_nickname;
    $this->timezone = get_option('timezone_string');
    $this->date = $date;
    //$this->address = cb_get_location_address(strtolower($location_nickname));
    //$this->get_api_geocode();
    $location = new CB_Location('name', $location_nickname);
    $this->location = $location;
    $this->address = $location->address;
    $this->params = array(
      'lat'=>$location->latitude,
      'lng'=>$location->longitude,
      'date'=>$this->date
    );
    $this->lat = $location->latitude;
    $this->lng = $location->longitude;
    $this->set_sunset();
  }

  public function get_charter_start_time ($duration){
    $chartertimes = array();
    $duration = (float)$duration;
    $hour = floor($duration);
    $minutes = ($duration-$hour)*60;
    $sunsettime = new DateTime($this->date.' '.$this->sunset);
    $chartertimes['sunset'] = $sunsettime->format('g:i A T');
    $twilight = new DateTime($this->date.' '.$this->twilight, new DateTimeZone($this->timezone));
    $twilight->sub(new DateInterval("PT".$hour."H".$minutes."M00S"));
    $chartertimes['boarding_datetime_object'] = $twilight;
    $chartertimes['boarding'] = $twilight->format('g:i A T');
    return $chartertimes;
  }

  /**
   * Set Sunset
   *
   * checks if sunset is already in db, if so set. if not, fetch from api
   *
   */
  private function set_sunset(){
    global $wpdb;
    $qry = "select * from wp_cb_sunset_times
      where location_name='".$this->location_name."' AND date(sunset) = '".$this->date."'";
    $result = $wpdb->get_row($qry);
    if($result){
      $this->sunset_mysql = $result->sunset;
      $this->twilight_mysql = $result->twilight;
      $sunset = new DateTime($result->sunset, new DateTimeZone($this->timezone));
      $this->sunset = $sunset->format('g:i A T');
      $twilight = new DateTime($result->twilight, new DateTimeZone($this->timezone));
      $this->twilight = $twilight->format('g:i A T');
    } else {
      $this->calculate_sunset();
      $this->commit_sunset();
    }
  }

  private function calculate_sunset(){
    date_default_timezone_set(get_option('timezone_string'));
    $date_sun_info = date_sun_info(strtotime($this->date), $this->lat, $this->lng);
    $this->date_sun_info = $date_sun_info;
    $this->sunset = date('g:i A T', $date_sun_info['sunset']);
    $this->sunset_mysql = date('Y-m-d H:i:s', $date_sun_info['sunset']);
    $this->twilight = date('g:i A T', $date_sun_info['civil_twilight_end']);
    $this->twilight_mysql = date('Y-m-d H:i:s', $date_sun_info['civil_twilight_end']);
    $this->sunrise = date('g:i A T', $date_sun_info['sunrise']);
    $this->sunrise_mysql = date('Y-m-d H:i:s', $date_sun_info['sunrise']);
    $this->twilight_start = date('g:i A T', $date_sun_info['civil_twilight_begin']);
    $this->twilight_start_mysql = date('Y-m-d H:i:s', $date_sun_info['civil_twilight_begin']);
  }

  private function commit_sunset(){
    global $wpdb;
    $wpdb->insert(
      'wp_cb_sunset_times',
      array(
        'sunset'=>$this->sunset_mysql,
        'twilight'=>$this->twilight_mysql,
        'location_name'=>$this->location_name,
        'sunrise'=>$this->sunrise_mysql,
        'twilight_start'=>$this->twilight_start_mysql
      )
    );
  }

}


 ?>
