<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * Calls to Open Weather API
 * just basic set up and calls etc...and a shortcode
 */


add_shortcode('open_weather_forecast', __NAMESPACE__ . '\\open_weather_forecast_shortcode');

function open_weather_forecast_shortcode ($atts){
    $weather = new CB_OpenWeather('avon');
    echo '<pre>'; var_dump($weather); echo '</pre>';
    //$content = cb_find_next_available(1196);
    $content = '<div class="owf-container"></div><a class="btn btn-secondary get-forecast clickable">get forecast</a>';
    return $content;
}

add_action( 'wp_ajax_nopriv_cb_get_forecast', __NAMESPACE__ . '\\cb_get_forecast_callback' );
add_action( 'wp_ajax_cb_get_forecast', __NAMESPACE__ . '\\cb_get_forecast_callback' );

function cb_get_forecast_callback(){
  $response = array();
  $booking = new CB_Booking(96);
  $response['booking'] = $booking;
  $weather = new CB_OpenWeather('avon');
  $response['weather'] = $weather;
  wp_send_json($response);
}


class CB_OpenWeather{
  public $forecast;
  public $weather_units;
  public $location;
  public $url;
  public $args;
  private $appid;

  public function __construct($location_nickname){
    $appid = get_option('cb_open_weather_key');
    $this->weather_units = get_option('open_weather_units');
    $location = new CB_Location('name', $location_nickname);
    $this->location = $location;
    $params = array(
      'lat'=>$location->latitude,
      'lon'=>$location->longitude,
      'units'=>$this->weather_units,
      'appid'=>$appid
    );
    $this->url = 'http://api.openweathermap.org/data/2.5/forecast';
    $this->args = array(
      'body'=>$params
    );
    $this->forecast = $this->get_forecast();
  }

  public function get_forecast(){
    $response = wp_remote_get($this->url, $this->args);
    $body =  wp_remote_retrieve_body( $response ) ;
    $body = json_decode($body);
    $body = $this->digest_body($body);
    return $body;
  }

  private function digest_body($body){
    $forecast = array();
    foreach($body->list as $list){
      $temp_f = $this->k_to_f($list->main->temp);
      $wind_direction = $this->wind_cardinals(68);
      $local_time = $this->forecast_timezone($list->dt_txt);
      $forecast[$list->dt_txt]=array(
        'date_time'=>$list->dt_txt,
        'local_time'=>$local_time,
        'condition'=>$list->weather[0]->description,
        'icon'=>$list->weather[0]->icon,
        'icon_src'=>$this->icon_src($list->weather[0]->icon),
        'wind_speed'=>$list->wind->speed,
        'wind_angle'=>$list->wind->deg,
        'wind_direction'=>$wind_direction,
        'temperature'=>$temp_f
      );
    }
    return $forecast;
  }

  public function forecast_timezone($utc_datetime){
    $UTC = new DateTimeZone("UTC");
    $timezone = get_option('timezone_string');
    $newTZ = new DateTimeZone($timezone);
    $date = new DateTime( $utc_datetime, $UTC );
    $date->setTimezone( $newTZ );
    return $date->format('Y-m-d H:i:s');
  }

  public function closest_forecast_date($array, $date){
    foreach($array as $day){
        $interval[] = abs(strtotime($date) - strtotime($day));
    }
    asort($interval);
    $closest = key($interval);
    return $closest;
  }

  public function get_weather_widget_html($date, $icon='display'){
    $forecast = $this->get_booking_forecast($date);
    $datetime = date('l h:i a', strtotime($forecast['local_time']));
    //echo '<pre>';var_dump($forecast);echo '</pre>';
    $content = '<div class="weather_wrapper">';
    $content .= ($icon == 'display')
      ? '<div><img src="'.$forecast['icon_src'].'"/></div>'
      : '' ;
    $content .= '<div><p class="weather-title">'.$this->location->name.' '.$forecast['temperature'].'&deg;f</p>
    <p>'.ucwords($forecast['condition']).
    '<br>Winds '.$forecast['wind_speed'].' mph ('.$forecast['wind_direction'].')</p></div>';
    $content .= '</div>';
    return $content;
  }

  public function get_weather_email_html($date, $icon='display'){
    $forecast = $this->get_booking_forecast($date);
    $datetime = date('l h:i a', strtotime($forecast['local_time']));
    //echo '<pre>';var_dump($forecast);echo '</pre>';
    $content = '<div class="weather_wrapper">';
    $content .= ($icon == 'display')
      ? '<div><img src="'.$forecast['icon_src'].'"/></div>'
      : '' ;
    $content .= '<div><span class="weather-title" style="margin: 0;">'.$this->location->name.' '.$forecast['temperature'].'&deg;f
    <br>'.ucwords($forecast['condition']).
    '<br>Winds '.$forecast['wind_speed'].' mph ('.$forecast['wind_direction'].')</span></div>';
    $content .= '</div>';
    return $content;
  }

  public function get_booking_forecast($charter_date){
    $charter_date = $this->timezone_to_utc($charter_date);
    $interval = array();
    foreach($this->forecast as $date=>$forecast){
      $diff = abs(strtotime($date) - strtotime($charter_date));
      $interval[]=array(
        'diff'=>$diff,
        'forecast_date'=>$date
      );
    }
    usort($interval, function($a, $b) {
      return $a['diff'] - $b['diff'];
    });
    $forecast_key = $interval[0]['forecast_date'];
    return $this->forecast[$forecast_key];
  }

  private function timezone_to_utc($datetime){
    $website_timezone = get_option('timezone_string');
    $website_time = new DateTime($datetime, new DateTimeZone($website_timezone));
    $website_time->setTimezone(new DateTimeZone('UTC'));
    return $website_time->format('Y-m-d H:i:s');
  }

  private function k_to_f($temp) {
    if ( !is_numeric($temp) ) { return false; }
    return round((($temp - 273.15) * 1.8) + 32);
  }

  private function icon_src($icon){
    $src = 'http://openweathermap.org/img/wn/'.$icon.'@2x.png';
    return $src;
  }

  private function wind_cardinals($deg) {
  	$cardinalDirections = array(
  		'N' => array(348.75, 360),
  		'N' => array(0, 11.25),
  		'NNE' => array(11.25, 33.75),
  		'NE' => array(33.75, 56.25),
  		'ENE' => array(56.25, 78.75),
  		'E' => array(78.75, 101.25),
  		'ESE' => array(101.25, 123.75),
  		'SE' => array(123.75, 146.25),
  		'SSE' => array(146.25, 168.75),
  		'S' => array(168.75, 191.25),
  		'SSW' => array(191.25, 213.75),
  		'SW' => array(213.75, 236.25),
  		'WSW' => array(236.25, 258.75),
  		'W' => array(258.75, 281.25),
  		'WNW' => array(281.25, 303.75),
  		'NW' => array(303.75, 326.25),
  		'NNW' => array(326.25, 348.75)
  	);
  	foreach ($cardinalDirections as $dir => $angles) {
  			if ($deg >= $angles[0] && $deg < $angles[1]) {
  				$cardinal = $dir;
  			}
  		}
  		return $cardinal;
  }

}

?>
