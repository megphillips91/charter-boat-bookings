<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

class CB_Calendar {
  public $month;
  public $year;
  public $product_id;
  public $action;
  public $list_action;

  function __construct($type = NULL, $month=NULL, $year=NULL, $product_id=NULL, $action=NULL){
    $this->list_action = $action;
    $this->month = date('m');
    $this->year = date('Y');
    $this->type = $type;
    $this->product_id = $product_id;
    } //end construct

    protected function calendar_heading($timestamp){
      /* table headings */
      $calendar = '<div class="calendar-month align-center">'.
      $this->prev_arrow($this->month, $this->year, 'month', $this->product_id).
      ' <span class="month-headline">'.date('M', $timestamp).' '.date('Y', $timestamp).'</span> '.$this->fwd_arrow($this->month, $this->year, 'month', $this->product_id).'</div>';
      $headings = array('Sun','Mon','Tues','Wed','Thur','Fri','Sat');
      $calendar.= '<tr class="calendar-row"><td class="calendar-day-head">'.implode('</td><td class="calendar-day-head">',$headings).'</td></tr>';
      return $calendar;
    }

  /* draws the calendar */
  public function draw_calendar(){

    $timestamp = mktime(0,0,0,$this->month,1,$this->year);
    $calendar = '<div class="cb-calendar-container" id="'.rand().'"><div class="cb-hold-calendar-loader"><span class="fa-stack fa-lg">
  	<i class="fa fa-circle fa-stack-2x"></i>
  	<i class="fa fa-compass fa-inverse fa-spin fa-stack-2x"></i>
  </span></div>';
    $calendar .= '<table cellpadding="0" cellspacing="0" class="calendar">';

    /* table headings */
    $calendar .= $this->calendar_heading($timestamp);
  //  wp_send_json($calendar);
    /* days and weeks vars now ... */
    $running_day = date('w',mktime(0,0,0,$this->month,1,$this->year));//day of the week for the first day of the month.
    $days_in_month = date('t',mktime(0,0,0,$this->month,1,$this->year));//number days in the month
    $days_in_this_week = 1;
    $day_counter = 0;
    $dates_array = array();

    /* row for week one */
    $calendar.= '<tr class="calendar-row">';

    /* print "blank" days until the first of the current week */
    for($x = 0; $x < $running_day; $x++):
      $calendar.= '<td class="calendar-day-np no-date"> </td>';
      $days_in_this_week++;
    endfor;

    /* keep going with days.... */
    for($list_day = 1; $list_day <= $days_in_month; $list_day++):
      $thisdate = $this->year.'-'.$this->month.'-'.$list_day;
      $calendar .= $this->get_td($thisdate);
      if($running_day == 6):
        $calendar.= '</tr>';
        if(($day_counter+1) != $days_in_month):
          $calendar.= '<tr class="calendar-row">';
        endif;
        $running_day = -1;
        $days_in_this_week = 0;
      endif;
      $days_in_this_week++; $running_day++; $day_counter++;
    endfor;

    /* finish the rest of the days in the week */
    if($days_in_this_week < 8):
      for($x = 1; $x <= (8 - $days_in_this_week); $x++):
        $calendar.= '<td class="calendar-day-np"> </td>';
      endfor;
    endif;

    /* final row */
    $calendar.= '</tr>';

    /* end the table */
    $calendar.= '</table></div>';

    /* all done, return result */
    return $calendar;
  }

  private function prev_arrow($month, $year, $view, $product_id){
    if($view == 'month'){
      $lastmonth = new Datetime($year.'-'.$month.'-01 '.' last month');
      $content = '<a class="pad-large cb-refresh-calendar" type="'.$this->type.'" date="'.$lastmonth->format('Y-m-d').'" product_id="'.$product_id.'" view="'.$view.'" list_action="'.$this->list_action.'">';
      $content .= '<i class="fa fa-backward"></i>';
      $content .= '</a>';
    } else {
      $lastmonth = new Datetime($date.' yesterday');
      $content = '<a class="pad-large cb-refresh-calendar" type="'.$this->type.'" product_id="'.$product_id.'" date="'.$lastmonth->format('Y-m-d').'" view="'.$view.'" list_action="'.$this->list_action.'">';
      $content .= '<i class="fa fa-backward"></i>';
      $content .= '</a>';
    }
    return $content;
  }

  private function fwd_arrow($month, $year, $view, $product_id){
    if($view == 'month'){
      $nextmonth = new Datetime($year.'-'.$month.'-01 '.' next month');
      $content = '<a class="pad-large cb-refresh-calendar" type="'.$this->type.'" product_id="'.$product_id.'" date="'.$nextmonth->format('Y-m-d').'" view="'.$view.'" list_action="'.$this->list_action.'">';
      $content .= '<i class="fa fa-forward"></i>';
      $content .= '</a>';
    } else {
      $date = strtotime($date);
      $date = strtotime("+7 day", $date);
      //echo date('Y-m-d', $date).'<br>';
      $content = '<a class="pad-large cb-refresh-calendar" type="'.$this->type.'" date="'.date('Y-m-d',$date).'" view="'.$view.'" list_action="'.$this->list_action.'">';
      $content .= '<i class="fa fa-forward"></i>';
      $content .= '</a>';
    }
    return $content;
  }

  protected function get_td($date){
    $today = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $calendardate = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
    $content = '<td class="calendar-day ';
    $content .= ($today->format('Y-mm-d') == $calendardate->format('Y-mm-d')) ? ' current-date ' : ' ';
    $content .= ' ">';
    $content .= $this->get_link($date, NULL);
    $content .= '</td>';
    return $content;
  }

  protected function get_link($date, $availability){
    $calendardate = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
    $dateformat = $calendardate->format('Y-m-d');
    $content = '<a class="clickable "
    date="'.$dateformat.'">
    <span class="day-number">'.date('j', strtotime($date)).'</span></a>';
    return $content;
  }

} //end class declaration


/* so we are going to extend the calendar class by looping through all booking products to check availability for each day for each product. */
class CB_Global_Calendar extends CB_Calendar {
  public $html;
  public $list_action;

  public function __construct($type=NULL, $month=NULL, $year=NULL, $product_id=NULL, $action=NULL){
    $this->list_action = $action;
    $this->month = ($month == NULL) ? date('m') : $month;
    $this->year = ($year == NULL) ?  date('Y') : $year;
    $this->type = 'global';
    $this->html = $this->draw_calendar();
  }

  protected function get_td($date){
    $today = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $calendardate = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
    $availability = new CB_Global_Availability($date);
    $content = '<td date="'.$calendardate->format('Y-m-d').'" class="calendar-day ';
    $content .= ($today->format('Y-m-d') == $calendardate->format('Y-m-d')) ? ' current-date ' : ' ';
    $content .= ($availability->available) ? '" >': ' none " >';
    $content .= $this->get_link_availability($date, $availability);
    $content .= '</td>';
    return $content;
  }

  protected function get_link($date, $availability){
    $content = '
    <span class="day-number">'.date('j', strtotime($date)).'</span>';
    return $content;
  }

  protected function get_link_availability($date, $availability){
    if($availability->available){
      $content = '<a class="clickable cb-list-products "
      date="'.$date.'" list_action="'.$this->list_action.'">
      <span class="day-number">'.date('j', strtotime($date)).'</span></a>';
    } else {
      $content = '
      <span class="day-number">'.date('j', strtotime($date)).'</span>';
    }
    return $content;
  }

} //end class declaration

class CB_Product_Calendar extends CB_Calendar {
  public $html;

  public function __construct($type=NULL, $month=NULL, $year=NULL, $product_id=NULL, $action=NULL){
    $this->action = $action;
    if($product_id == NULL){
      trigger_error("Must Provide product_id (int) for Class CB Product Calendar", E_USER_ERROR);
    }
    $this->product_id = $product_id;
    $this->month = ($month == NULL) ? date('n') : $month;
    $this->year = ($year == NULL) ?  date('Y') : $year;
    $this->type = 'product';
    $this->html = $this->draw_calendar();
  }

  protected function get_td($date){
    $today = new DateTime(NULL, new DateTimeZone(get_option('timezone_string')));
    $calendardate = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
    $availability = new CB_Product_Availability($date, $this->product_id);
    $content = '<td date="'.$calendardate->format('Y-m-d').'" class="calendar-day ';
    $content .= ($today->format('Y-m-d') == $calendardate->format('Y-m-d')) ? ' current-date ' : ' ';
    $content .= ($availability->available) ? '" >' : ' none " >' ;
    $content .= $this->get_link_availability($date, $availability);
    $content .= '</td>';
    return $content;
  }

  protected function get_link($date, $availability){
    $calendardate = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
    $dateformat = $calendardate->format('Y-m-d');
    $content = '
    <span class="day-number">'.date('j', strtotime($date)).'</span>';
    return $content;
  }

  protected function get_link_availability($date, $availability){
    if($availability->available){
      $calendardate = new DateTime($date, new DateTimeZone(get_option('timezone_string')));
      $dateformat = $calendardate->format('Y-m-d');
      $content = '<a class="clickable ';
      $content .= ' cb-product-calendar-link " date="'.$dateformat.'" product_id="'.$this->product_id.'" list_action="'.$this->action.'">';
      //$content .= $this->get_product_type($date).'-tocart " date="'.$date.'" product_id="'.$this->product_id.'">';
      $content .= '<span class="day-number">'.date('j', strtotime($date)).'</span></a>';
    } else {
      $content = '
      <span class="day-number">'.date('j', strtotime($date)).'</span>';
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
    return $product_type;
  }

} //end class declaration


?>
