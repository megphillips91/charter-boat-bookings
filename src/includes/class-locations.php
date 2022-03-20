<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

class CB_Locations {
  public $number;
  public $locations;

  public function __construct(){
    $this->get_number();
    $this->get_locations();
  }

  private function get_number(){
    global $wpdb;
    $qry = "select option_name from ".$wpdb->prefix."options
where option_name like '%cb_location_name_%'
AND option_name NOT like '%dep_cb_location_name_%'";
    $results = $wpdb->get_results($qry);
    $this->number = count($results);
  }

  private function get_locations(){
    $locations = array();
    for($x=1; $x<=$this->number; $x++){
        $locations[] = new CB_Location('id', $x);
    }
    $this->locations = $locations;
  }

}

class CB_Location {
  public $id;
  public $name;
  public $address;
  public $latitude;
  public $longitude;

  public function __construct($field, $value){
      $this->get_location_by($field, $value);
  }

  private function get_location_by($field, $value){
    switch ($field) {
      case 'id':
        $this->id = $value;
        $this->address = get_option('cb_location_address_'.$this->id);
        $this->name = get_option('cb_location_name_'.$this->id);
        $this->latitude = get_option('cb_location_latitude_'.$this->id);
        $this->longitude = get_option('cb_location_longitude_'.$this->id);
        break;
      case 'name':
        $this->get_id($value);
        $this->address = get_option('cb_location_address_'.$this->id);
        $this->name = get_option('cb_location_name_'.$this->id);
        $this->latitude = get_option('cb_location_latitude_'.$this->id);
        $this->longitude = get_option('cb_location_longitude_'.$this->id);
        break;
    }
  }

  private function get_id($name){
    global $wpdb;
    $qry = "select option_name from ".$wpdb->prefix."options where option_value = '".$name."' and option_name like '%cb_location_name_%' limit 1";
    $row = $wpdb->get_row($qry);
    $str = $row->option_name;
    preg_match_all('!\d+!', $str, $matches);
    $this->id = implode(' ', $matches[0]);
  }

  public function deactivate_location(){
    $fields = array('address', 'latitude', 'longitude', 'name');
    foreach($fields as $field){
      $value = $this->$field;
      $current_number = get_option('cb_number_locations');
      $option = 'cb_location_'.$field.'_'.$this->id;
      update_option('dep_'.$option, $value);
      delete_option($option);
      /* reduce number by 1 */
      $current_number = get_option('cb_number_locations');
      $number = ((int)$current_number) - 1 ;
    }
  }

}



 ?>
