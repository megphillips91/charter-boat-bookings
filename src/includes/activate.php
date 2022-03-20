<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

function cb_admin_errors() {
	$notice  = '
		<div class="notice notice-error is-dismissible">
				<p>'.'Charter Bookings cannot work properly if your timezone is not set. Navigate to <a href="'.admin_url().'options-general.php">general settings</a> to set your site timezone.'.'</p>
		</div>';
	if(!get_option('timezone_string') || empty(get_option('timezone_string'))){
		echo $notice;
	}
	$notice = '
		<div class="notice notice-error">
				<p>'.'Charter Bookings is a WooCommerce Extension. It cannot work without WooCommerce Installed and Activated. <a href="'.admin_url('plugin-install.php?s=woocommerce&tab=search&type=term').'">Install WooCommerce Now</a>.'.'</p>
		</div>';
	if(!class_exists('WC_Product', false)){
		echo $notice;
	}
	$notice = '
		<div class="notice notice-error is-dismissible">
				<p>'.'Charter Bookings cannot display the weather and will not work properly without an OpenWeather API key. Navigate to <a href="https://openweathermap.org/" target="_blank">Open Weather</a> to sign up for an Open Weather API Key. The Open Weather API is free to join and free to use. Once you have your API key, navigate to <a href="'.admin_url().'/admin.php?page=wc-settings&tab=products&section=cb_bookings">WooCommerce/Products/Charter Bookings</a> to enter key'.'</p>
		</div>';
		if(!get_option('cb_open_weather_key') || empty(get_option('cb_open_weather_key'))){
			echo $notice;
		}

}
add_action('admin_notices', __NAMESPACE__ . '\\cb_admin_errors');

/**
 * Block Initializer.
 */
//core business functionality affecting both forward and admin facing pages

require_once plugin_dir_path( __FILE__ ) . 'class-calendar.php';
require_once plugin_dir_path( __FILE__ ) . 'class-booking.php';
require_once plugin_dir_path( __FILE__ ) . 'class-booking-query.php';
require_once plugin_dir_path( __FILE__ ) . 'class-booking-order.php';
require_once plugin_dir_path( __FILE__ ) . 'class-charters.php';
require_once plugin_dir_path( __FILE__ ) . 'woo-booking.php';
require_once plugin_dir_path( __FILE__ ) . 'woo-booking-variations.php';
require_once plugin_dir_path( __FILE__ ) . 'woo-product-helper-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'class-locations.php';
require_once plugin_dir_path( __FILE__ ) . 'class-blackouts.php';
require_once plugin_dir_path( __FILE__ ) . 'class-boat.php';
require_once plugin_dir_path( __FILE__ ) . 'class-product-charter-booking.php';
require_once plugin_dir_path( __FILE__ ) . 'ajax/ajax-functions.php';
require_once plugin_dir_path( __FILE__ ) . 'class-sunset-time.php';
require_once plugin_dir_path( __FILE__ ) . 'class-open-weather.php';
require_once plugin_dir_path( __FILE__ ) . 'class-availability.php';
require_once plugin_dir_path( __FILE__ ) . 'class-customer-notifications.php';
require_once plugin_dir_path( __FILE__ ) . 'class-admin-notifications.php';

//affect public facing pages
require_once plugin_dir_path( __FILE__ ) . '../../src/public/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/public/woo-single-booking-display.php';
//require_once plugin_dir_path( __FILE__ ) . '../../src/public/woo-display.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/public/class-list-products.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/public/ajax/ajax-functions.php';


//affect admin pages
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/admin-menu.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/settings-tab.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/ajax/ajax-functions.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/woo-booking-product-admin.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/class-admin-calendar.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/class-admin-booking.php';
require_once plugin_dir_path( __FILE__ ) . '../../src/admin/class-admin-form.php';

/**
 * Block Initializer.
 */

 /* both side facing scripts and styles */
 function cb_includes_scripts(){
	wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.11.2/css/all.css');
 	wp_enqueue_style('msp-calendar-styles', plugin_dir_url( __FILE__ ).'css/calendar-styles.css');
 	wp_enqueue_style('chbk-product-listing-styles', plugin_dir_url( __FILE__ ).'css/product-listing.css');
 	wp_enqueue_script( 'chbk_bookings-js',plugin_dir_url( __FILE__ ).'js/charter-bookings.js', array( 'jquery' ),'',true );
	wp_localize_script( 'chbk_bookings-js', 'chbk_bookings_vars',
		array(
			'admin_ajax'=>admin_url( 'admin-ajax.php' )
		)

	);
 }
 add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\cb_includes_scripts' );
 add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\cb_includes_scripts' );

/* public facing scripts and styles */
function msp_cb_scripts(){
	wp_enqueue_style('charter-bookings-public-styles', plugin_dir_url( __FILE__ ).'../../src/public/css/public.css');
	wp_enqueue_script('chbk_public_js', plugin_dir_url( __FILE__ ).'../../src/public/js/cb-public.js', array( 'jquery' ),'',true );
	wp_localize_script( 'chbk_public_js', 'chbk_public_vars',
		array(
			'admin_ajax'=>admin_url( 'admin-ajax.php' ),
			'home'=>site_url(),
			'namespace'=>__NAMESPACE__ ,
		)
	);
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\msp_cb_scripts' );

/* admin facing scripts */
function cb_admin_scripts(){
	wp_enqueue_style('charter-bookings-admin-styles', plugin_dir_url( __FILE__ ).'../../src/admin/css/admin.css');
	wp_enqueue_script( 'chbk_admin_scripts',plugin_dir_url( __FILE__ ).'../../src/admin/js/cb-admin-scripts.js', array( 'jquery' ),'',true );
	wp_localize_script( 'chbk_admin_scripts', 'chbk_admin_scripts_vars',
		array(
			'admin_ajax'=>admin_url( 'admin-ajax.php' )
		)
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\cb_admin_scripts' );


/**
 * =======================================
 * ACTIVATION HOOK
 * functions to be called on activation
 * =======================================
 */
//register_activation_hook( __FILE__, 'cb_activate' );

function cb_activate(){
	cb_cron_notifications();
	cb_maybe_create_tables();
	cb_set_options();
	cb_create_pages();
}

function cb_cron_notifications(){
	$cb_notifications = array(
		'cb_send_customer_reminders',
		'cb_send_admin_reminders'
	);
	foreach($cb_notifications as $cb_notification){
		if ( ! wp_next_scheduled( $cb_notification ) ) {
	    wp_schedule_event( time(), 'daily', $cb_notification );
	  }
	}
}

function cb_maybe_create_tables(){
	global $wpdb;
	$admin_abspath = str_replace( site_url(), ABSPATH, admin_url() );
	$admin_php_path = $admin_abspath . 'includes/upgrade.php';
		require_once( $admin_php_path);
		$charset_collate = $wpdb->get_charset_collate();

		//=== listings table
		$table_name = $wpdb->prefix . 'charter_bookings';
		$sql = "CREATE TABLE $table_name (
			id int(11) unsigned NOT NULL AUTO_INCREMENT,
		  booking_status varchar(100) DEFAULT NULL,
		  product_id int(30) DEFAULT NULL,
		  reservation_id int(30) DEFAULT NULL,
		  orderid_reservation int(30) DEFAULT NULL,
		  charter_date datetime DEFAULT NULL,
		  duration float DEFAULT NULL,
		  location varchar(100) DEFAULT NULL,
		  persons int(11) DEFAULT NULL,
		  billing_email varchar(100) DEFAULT NULL,
		  billing_phone varchar(100) DEFAULT NULL,
		  balance_id int(30) DEFAULT NULL,
		  orderid_balance int(30) DEFAULT NULL,
		  PRIMARY KEY (id)
		) $charset_collate;";
		maybe_create_table($table_name, $sql );

		//=== sunset table
		$table_name = $wpdb->prefix . 'cb_sunset_times';
		$sql = "CREATE TABLE $table_name (
			id int(11) unsigned NOT NULL AUTO_INCREMENT,
  		location_name varchar(100) DEFAULT NULL,
  		sunset datetime DEFAULT NULL,
  		twilight datetime DEFAULT NULL,
			sunrise datetime DEFAULT NULL,
  		twilight_start datetime DEFAULT NULL,
		  PRIMARY KEY (id)
		) $charset_collate;";
		maybe_create_table($table_name, $sql );
}

function cb_set_options(){
	add_option( 'cb_number_locations', 1);
	add_option('cb_weeks_advance', 52);
	add_option('cb_durations', '4 | 8');
	add_option('cb_same_day_buffer', 30);
	add_option('cb_sunset_api', 'yes');
}

function cb_create_pages(){
	$post_content = '<!-- wp:shortcode -->
		[charter_booking_confirmation]
		<!-- /wp:shortcode -->';
	$args = array(
		'post_content'=>$post_content,
		'post_title'=>'Charter Confirmation',
		'post_status'=>'publish',
		'post_type'=>'page',
		'comment_status'=>'closed'
	);
	if(!post_exists('Charter Confirmation')){
		wp_insert_post($args);
	}
}

 ?>
