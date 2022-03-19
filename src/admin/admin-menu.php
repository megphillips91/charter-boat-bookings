<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;


function register_cb_charter_bookings_submenu() {
    add_submenu_page( 'woocommerce', 'Bookings', 'Bookings', 'manage_options', 'cb-bookings', __NAMESPACE__ . '\\cb_charter_bookings_submenu_page_callback' );
}

function cb_charter_bookings_submenu_page_callback (){
  $vars = array('cb_admin_view', 'cb_admin_bookings_status', 'cb_admin_booking_status', 'cb_admin_date_range', 'booking_status', 'date_range');
  foreach($vars as $var){
    if(isset($_SESSION[$var])){unset($_SESSION[$var]);}
  }
  $args = array(
    'future'=>'future'
  );
  $bookings = new CB_Booking_Query($args, 'future');
  $view = ( isset($_GET['view']) ) ? ess_attr($_GET['view']) : 'table';
  $admin_page = new CB_Admin_Page($view, $bookings);
  echo $admin_page->html;
}
add_action('admin_menu', __NAMESPACE__ . '\\register_cb_charter_bookings_submenu', 10);




class CB_Admin_Week  {
  public $bookings;
  public $html;

  public function __construct($bookings){
    $this->bookings = $bookings;
    $calendar = new CB_Admin_Calendar('week', $bookings);
    $this->html = $calendar->html;
  }
}

class CB_Admin_Day  {
  public $bookings;
  public $html;

  public function __construct($bookings){
    $this->bookings = $bookings;
    $this->html = '';
  }
}

class CB_Admin_Table  {
  public $bookings;
  public $html;

  public function __construct($bookings){
    $this->bookings = $bookings;
    $this->html = $this->table();
  }

  private function table(){
    $content = '<div class="cb-admin-bookings">';
    $content .= '<table class="wp-list-table widefat fixed striped posts">';
    $content .= $this->table_head();
    $content .= $this->table_body();
    $content .= '</table></div>';
    return $content;
  }

  private function table_head(){
    $content = '<thead><tr>';
      /*$content .=  '
      <td id="cb" class="manage-column column-cb check-column"><label class="screen-reader-text" for="cb-select-all-1">Select All</label><input id="cb-select-all-1" type="checkbox"></td>';*/
      $content .= '<th scope="col" id="actions" class="narrowest"></th>';
      $content .= '
      <th scope="col" id="booking" class="manage-column column-booking column-primary sortable desc"><a href="" ><span>Booking</span><span class="sorting-indicator"></span></a></th>
      <th scope="col" id="booking" class="manage-column column-booking_date sortable desc"><a href="" ><span>Date</span><span class="sorting-indicator"></span></a></th>
      <th scope="col" id="booking_location" class="manage-column column-booking_location">Location</th>
      <th scope="col" id="booking_persons" class="manage-column column-booking_persons">Persons</th>
      <th scope="col" id="booking_status" class="manage-column column-booking_status">Booking Status</th>
      <th scope="col" id="booking_order" class="manage-column column-booking_order">Order</th>
      ';
    $content .= '</tr></thead>';
    return $content;
  }

  private function table_body(){
    if(isset($this->bookings->bookings)){
      $content = '<tbody id="the-list">';
      foreach($this->bookings->bookings as $booking){
        $content .= $this->table_row($booking);
      }
      $content .= '</tbody>';
      return $content;
    }
  }

  function table_row($booking){
    //echo '<pre>'; var_dump($booking); echo '</pre>';
    $content = '';
    $content .= '<tr
      id="booking-'.$booking->id.'"
      class="iedit author-self level-0 booking-'.$booking->id.' type-booking hentry"
      >';

    $content .= '<td
        class="title column-booking_actions " data-colname="Actions"><strong><a href="tel:'.$booking->billing_phone.'"><i class="fas fa-phone"></i></a><br><a href="mailto:'.$booking->billing_email.'" target="_blank"><i class="fas fa-envelope"></i></a></strong></td>';
    $content .= '<td
      class="title column-booking has-row-actions column-primary " data-colname="Booking">
      <strong>
        <a class="row-booking cb-open-booking" booking_id="'.$booking->id.'"  aria-label="'.$booking->product_name.' (Open)">'.$booking->product_name.'</a>
      </strong>
      <br>
      <span>'.$booking->billing_name.'</span>
      </td>';
    $timezone = get_option('timezone_string');
    $end = new DateTime($booking->end_time, new DateTimeZone($timezone));
    $content .= '<td
        class="title column-booking_date " data-colname="Date"><strong>
          '.$booking->date_object->format("m-d-Y").'</strong><br><span class="cb-smaller-font">'.$booking->date_object->format("g:i a").'-'.$end->format("g:i a").'
        </span></td>';
    $content .= '<td
            class="title column-booking_location " data-colname="Location">'.$booking->location.'</td>';
    $content .= ($booking->has_persons)
        ? '<td class="title column-booking_persons " data-colname="Persons">'.$booking->persons.'</td>'
        : '<td class="title column-booking_persons " data-colname="Persons">Private</td>' ;
    $content .= '<td
            class="title column-booking_status " data-colname="Status">
            <mark class="order-status status-'.$booking->booking_status.'">
            <span>'.$booking->booking_status.'
            </span></mark></td>';
    $content .= '<td
            class="title column-booking_orders " data-colname="Order">
              <strong><a target="_blank" href="'.$this->cb_get_order_admin_link($booking->orderid_reservation).'" >
              #'.$booking->orderid_reservation.'</strong> ('.$booking->reservation_total.') </a>';
    $content .=  $this->balance_summary($booking);
    $content .='</td>';
    $content .= '</tr>';
    return $content;
  }

  private function cb_admin_page_table_foot(){

  }

  private function balance_summary($booking){
    //if there is a confirmation fee and it is already paid, show the order# and total
    if($booking->orderid_balance != NULL){
      $content = '<br><strong><a target="_blank" href="'.$this->cb_get_order_admin_link($booking->orderid_balance).'" >#'.$booking->orderid_balance.'</strong> ('.$booking->confirmation_total.')</a>';
      return $content;
    }
    //final balance is required, in the future, and has not been paid
    if($booking->orderid_balance == NULL && $booking->balanceduedate_in_future && $booking->final_balance != 0) {
      $content = '<br><span class="cb-grey">$'.$booking->final_balance.' Due on '.$booking->balance_due_date.'</span>';
      return $content;
    }
    //no final balance was ever Due
    if($booking->orderid_balance == NULL && $booking->balanceduedate_in_future == false){
      $content = '<br><span class="cb-grey">na</span>';
      return $content;
    }
  }

  public function cb_get_order_admin_link($order_id){
    $content = admin_url( 'post.php?post='.$order_id.'&action=edit' );
    return $content;
  }


} //end class definition table

class CB_Admin_Page {
  public $view;
  public $bookings;
  public $html;
  public $view_html;

  public function __construct($view, $bookings){
    $this->view = $view;
    $this->bookings = $bookings;
    $this->html = '<div class="wrap">
    <div class="cb-booking-modal-backdrop"></div>
    <div class="cb-hold-booking"></div>';
    $this->html .= $this->page_header();
    $this->html .= $this->page_header_nav();
    $admin_table_view = new CB_Admin_Table($this->bookings);
    $this->html .= $admin_table_view->html;
    $this->html .= '</div>';
  }

  public function set_view_html(){
      $admin_table_view = new CB_Admin_Table($this->bookings);
      $view_html = $admin_table_view->html;
      return $view_html;
  }

  public function page_header(){
    $content = '<h1 class="wp-heading-inline">Charter
  Bookings</h1><hr class="wp-header-end">';
    return $content;
  }

  public function page_header_nav(){
    if(session_status() === PHP_SESSION_NONE){session_start();}
    $content = '<ul class="subsubsub">
      <li> <a  class="booking_status_fitler ';
    $content .= (!isset($_SESSION['cb_admin_booking_status'])
    || ($_SESSION['cb_admin_booking_status'] == 'all') )
      ? ' current '
      : ' ';
    $content .= '" booking_status="all"> All | </a></li>
      <li> <a  class="booking_status_fitler';
    $content .= (isset($_SESSION['cb_admin_booking_status'])
    && ($_SESSION['cb_admin_booking_status'] == 'confirmed') )
      ? ' current '
      : ' ';
    $content .= '" booking_status="confirmed"> Confirmed | </a></li>
      <li> <a class="booking_status_fitler';
    $content .= (isset($_SESSION['cb_admin_booking_status'])
    && ($_SESSION['cb_admin_booking_status'] == 'reserved') )
      ? ' current '
      : ' ';
    $content .= '"  booking_status="reserved"> Reserved </a></li>
      </ul>';
    $content .= '<br clear="all" />';
  //  $content .= '<div>'.$this->view_switch();
    $content .= $this->tablenav();
    return $content;
  }

  private function view_switch(){
    $content = '<div class="view-switch float-right">';
    $content .= '<a class="view-switch-link ';
    $content .= (!isset($_SESSION['cb_admin_view']) || !isset($_SESSION['cb_admin_view']) == 'table') ? ' current ' : '';
    $content .= ' " view="table"><span class="dashicons dashicons-list-view"></span></a>';
    $content .= '<a class="view-switch-link" view="week"><span class="dashicons dashicons-schedule"></span></a>';
    $content .= '</div>';
    return $content;
  }

  private function tablenav(){
    if(session_status() === PHP_SESSION_NONE){session_start();}
    $content = '<div class="float-left">';
    $content .= '
    <label for="filter-dates" class="screen-reader-text">Filter By Booking Date</label>
      <select name="date_range" id="date_range">';
    
    $content .= '<option ';
    $content .= ( !isset($_SESSION['cb_admin_date_range']) || (isset($_SESSION['cb_admin_date_range']) && $_SESSION['cb_admin_date_range'] == 'future'  ))
      ? ' selected '
      : ' ';
    $content .= 'value="future">Future Bookings</option>
        <option ';
    $content .= (isset($_SESSION['cb_admin_date_range'])
    && ($_SESSION['cb_admin_date_range'] == 'past') )
      ? ' selected '
      : ' ';
    $content .= ' value="past">Past Bookings</option>
      </select>';
    $content .= '</div>';
    return $content;
  }

} //end CB_Admin_Page declaration




 ?>
