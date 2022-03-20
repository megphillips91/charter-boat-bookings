<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;
/**
 * Booking Product Type -- Helper Functions
 *
 * ========================================
 * Helper functions related to custom woocommerce product type
 * ========================================
 *
 */

//checks if charter booking
function is_charter_booking($product_id){
	$terms = get_the_terms($product_id, 'product_type');
	$product_type = (!empty($terms)) ? sanitize_title(current($terms)->name) : 'simple';
	if($product_type == 'charter_booking'){ return true;} else {return false;}
}

//get custom product meta
function get_charter_booking_meta($product_id){
	$booking_meta = array();
	$fields = array(
    '_cb_duration',
    '_cb_start_time',
    '_cb_location',
    '_cb_reservation_fee',
    '_cb_final_balance',
    '_cb_balance_due_date',
		'_cb_is_sunset',
		'_cb_date'
  );
	foreach($fields as $field){
		$booking_meta[$field] = get_post_meta($product_id, $field ,true);
	}
	return $booking_meta;
}

function calc_balance_due_date($charter_date){
  $charter_date = new DateTime($charter_date);
  $duedate = $charter_date->sub(new DateInterval("P3D"));
  return $duedate->format('Y-m-d');
}

function cb_pipe_array($options){
	$options = explode( '|', $options);
	$formatted = array();
	foreach($options as $option){
		$option = trim($option);
		$formatted[$option]=$option;
	}
	return $formatted;
}

function cb_get_location_options(){
	$locations = new CB_Locations();
	$names = array();
	foreach($locations->locations as $location){
		$names[$location->name]=$location->name;
	}
	return $names;
}

function cb_get_location_address($nickname){
	$addresses = get_option('cb_locations');
	$locations = cb_pipe_array(strtolower($addresses));
	foreach($locations as $location){
		$location = strtolower(trim($location));
		$address = explode(',', $location);
		foreach($address as $addy){
			trim($addy);
		}
		if(trim($address[1]) == $nickname){
			$flataddress = implode(', ', $address);
			return $flataddress;
		} else {
			return false;
		}
	}
}

function cb_calc_end_time($start_time, $duration){
	$timezone = get_option('timezone_string');
	$enddate = new DateTime($start_time, new DateTimeZone($timezone));
	$hoursminutes = cb_hours_mins($duration);
	if (strpos($duration, '.') !== false){
		$enddate->add(new DateInterval("PT".$hoursminutes['H']."H".$hoursminutes['M']."M"));
	} else {
		$enddate->add(new DateInterval("PT".$hoursminutes['H']."H"));
	}
	return $enddate->format("H:i:s");
}

/**
 * Get Product Starttime
 *
 * gets product starttime based on the date (takes into consideration that the sunset may make the starttime variable)
 *
 * @param  int $product_id
 * @param  string Y-m-d
 * @return object Datetime object
 */
function cb_get_product_starttime($product_id, $date){
	$timezone = get_option('timezone_string');
	$product_meta = get_charter_booking_meta($product_id);
	if($product_meta['_cb_is_sunset'] == 'yes'){
		$sunsettime = new CB_Sunset_Time($product_meta['_cb_location'], $date);
		$product_starttime = $sunsettime->get_charter_start_time($product_meta['_cb_duration']);
		$product_starttime = $product_starttime['boarding_datetime_object'];
	} else {
		$product_starttime = new DateTime($date.' '.$product_meta['_cb_start_time'], new DateTimeZone($timezone));
	}
	return $product_starttime;
}

/**
 * Return Hours and Minutes from Duration
 *
 * basically provides the needed information for PHP DateInterval
 *
 * @param  string $str_duration in hours float
 * @return array of integers
 */
function cb_hours_mins($str_duration){
	$duration = (float)$str_duration;
	$duration_hours = floor($duration);
	$duration_minutes = ($duration-$duration_hours)*60;
	return array('H'=>$duration_hours, 'M'=>$duration_minutes);
}


/**
 * Modify Woocommerce Meta Query to add support for _cb_location
	*
 */

add_filter( 'woocommerce_product_data_store_cpt_get_products_query', __NAMESPACE__ . '\\product_query_support_cb_location', 10, 3 );
function product_query_support_cb_location( $wp_query_args, $query_vars, $data_store_cpt ) {
    $meta_key = '_cb_location'; // The custom meta_key

    if ( ! empty( $query_vars[$meta_key] ) ) {
        $wp_query_args['meta_query'][] = array(
            'key'     => $meta_key,
            'value'   => sanitize_text_field( $query_vars[$meta_key] ),
            'compare' => '=', // <=== Here you can set other comparison arguments
        );
    }
    return $wp_query_args;
}
?>
