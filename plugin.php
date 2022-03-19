<?php
/**
 * Plugin Name: Charter Boat Bookings
 * Plugin URI: http://msp-media.org/projects/plugins/charter-bookings
 * Description: Charter Boat Bookings is a WooCommerce extension created specifically with Sailing Charters and Fishing Charters in mind. Charterboat Bookings includes industry specific features such as weather predictions and sunset dependant products. Book private or per person charters, and set your maximum passengar capacity. Minimize refunds with built-in reservation fee and final balance.
 * In particular, the charter boat industry is highly weather dependant and therein has many weather cancellations and/or refunds. Due to this, Charter Bookings accepts a reservation fee and a final balance fee rather than a full payment up front. This functionality proves ideal for the charter operator since the final balance is due only three days prior to the charter such that funds will typically not have transferred to charter operator's bank and therein providing customer refunds is much easier.
 * Author: megphillips91
 * Author URI: http://msp-media.org/
 * Requires at least: 5.6
 * Tested up to: 5.9.2
 * Requires PHP: 7.1.2
 * WC requires at least: 5.7
 * WC tested up to: 6.2
 * Version: 1.8
 * Stable tag: trunk
 * License: GPL2+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

 /*
 Charter Boat Bookings is free software: you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation, either version 2 of the License, or
 any later version.

 Charter Boat Bookings is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Charter Boat Bookings. If not, see http://www.gnu.org/licenses/gpl-3.0.html.
 */

namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Requires class initializer files
 *
 * init for all block js and
 * activate for all php class files
 *
 */
//require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
require_once plugin_dir_path( __FILE__ ) . 'src/includes/activate.php';
require_once plugin_dir_path( __FILE__ ) . 'src/includes/deactivate.php';

/**
 * =======================================
 * ACTIVATION HOOK
 * functions to be called on activation
 * find file includes/activate
 * =======================================
 */
register_activation_hook( __FILE__, __NAMESPACE__ . '\\cb_activate' );


/**
 * =======================================
 * HELPER FUNCTIONS
 * functions used in any context but not readily available in WP or PHP
 * =======================================
 */
//takes WP array of objects with only one attribute and returns a simple single dimensional array
function cb_wp_collapse($results, $fieldname){
	$array = array();
	foreach($results as $result){
		$array[]=$result->$fieldname;
	}
	return $array;
}

?>
