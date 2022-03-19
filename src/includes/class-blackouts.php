<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

class CB_Blackouts {
  public $number;
  public $blackouts;

  public function __construct(){
    $this->set_number();
    $this->set_blackouts();
  }

  private function set_number(){
    $number = get_option('cb_number_blackouts');
    $this->number = $number;
  }

  private function set_blackouts(){
    $blackouts = array();
    for($x = 0; $x <= $this->number; $x++){
      $blackouts[]= array(
        'start'=>get_option('cb_blackout_start_'.$x),
        'end'=>get_option('cb_blackout_end_'.$x),
      );
    }
    $this->blackouts = $blackouts;
  }

} //end class declaration


class CB_Blackout {
  public $id;
  public $start;
  public $end;

  public function  __construct($id){
    $this->set_blackout_by('id', $id);
  }

  private function set_blackout_by($field, $value){
    switch ($field) {
      case 'id' :
      $this->id = $value;
      $this->start = get_option('cb_blackout_start_'.$this->id);
      $this->end = get_option('cb_blackout_end_'.$this->id);
    }
  }

  public function deactivate(){
    $fields = array('start', 'end');
    foreach($fields as $field){
      $value = $this->$field;
      $option = 'cb_blackout_'.$field.'_'.$this->id;
      update_option('dep_'.$option, $value);
      delete_option($option);
    }
    /* reduce number by 1 */
    $current_number = get_option('cb_number_blackouts');
    $number = ((int)$current_number) - 1 ;
    update_option('cb_number_blackouts', $number);
  }

} //end class declaration

 ?>
