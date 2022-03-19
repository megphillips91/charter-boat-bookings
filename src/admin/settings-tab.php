<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;
// This is just a form sample

// Add a new section to WooCommerce > Settings > Products
function bookings_products_section( $sections ) {
	$sections['cb_bookings'] = __( 'Charter Bookings', 'cb-bookings' );
	return $sections;
}
add_filter( 'woocommerce_get_sections_products', __NAMESPACE__ . '\\bookings_products_section' );


function cb_location_settings($bookings_settings){
	/* settings for captain and capacity */
	$bookings_settings[] =	array(
		'title'     => __( 'Vessel', 'cb-bookings' ),
			'type'      => 'title',
			'desc'     => __( 'Please select the captain and capacity of your charters', 'woocommerce' ),
			'id'        => 'cb_bookings_captain_section',
	);
	$args = array(
		'role__in'=>array('administrator', 'shop_manager'),
		'fields'=>array('ID','display_name')
	);
	$users = get_users($args);
	$options = array();
	foreach($users as $user){
		$options[$user->ID]=$user->display_name;
	}
	$bookings_settings[] = array(
		'title' => __( 'Captain', 'cb-bookings' ),
		 'type' => 'select',
		'desc_tip' => __( 'User must be at least a shop manager to be included in the options', 'cb-bookings' ),
		'default'=>'',
			'id'=>'cb_captain',
			'options'=>$options,
	 );

	 $bookings_settings[] = array(
 	 'title' => __( 'Capacity', 'cb-bookings' ),
 		'type' => 'number',
 		'desc_tip' => __( 'Maximum number of guests allowed on board (integer required)', 'cb-bookings' ),
 		 'id'=>'cb_booking_capacity'
 	);

	$bookings_settings[] = array(
	'title' => __( 'Open Weather API Key', 'cb-bookings' ),
	'type'=>'text',
	'desc_tip' => __( 'Copy and Paste Your Open Weather API Key here.', 'cb-bookings' ),
	'id'=>'cb_open_weather_key'
 );

	$bookings_settings[] = array(
		'type'  => 'sectionend',
		'id'    => 'cb_bookings_captain_section',
	);
/*settings for location */
	$bookings_settings[] =	array(
		'title'     => __( 'Charter Location', 'cb-bookings' ),
			'type'      => 'title',
			'desc'     => __( 'Please enter the location from which you operate charters.', 'woocommerce' ),
			'id'        => 'cb_bookings_location_section',
	);


	$bookings_settings[] = array(
		'type'  => 'sectionend',
		'id'    => 'cb_bookings_location_section',
	);

	for($x = 1; $x <= get_option('cb_number_locations'); $x++){
		$titlenum = $x + 1;
		$bookings_settings[] =	array(
			'title'     => __( 'Location ', 'cb-bookings' ),
				'type'      => 'title',
				'id'        => 'cb_bookings_location_'.$x.'_section',
		);
		$bookings_settings[] = array(
		 'title' => __( 'Name', 'cb-bookings' ),
			'type' => 'text',
			'desc_tip' => __( 'Name to appear at checkout', 'cb-bookings' ),
			 'id'=>'cb_location_name_'.$x
		);

		$bookings_settings[] = array(
		 'title' => __( 'Address', 'cb-bookings' ),
			'type' => 'text',
			'desc_tip' => __( 'Format: Street, City, State, Zip, Country', 'cb-bookings' ),
			 'id'=>'cb_location_address_'.$x
		);
		$bookings_settings[] = array(
		 'title' => __( 'Latitude', 'cb-bookings' ),
			'type' => 'text',
			'desc_tip' => __( 'Enter the latitude of this charter location', 'cb-bookings' ),
			 'id'=>'cb_location_latitude_'.$x
		);
		$bookings_settings[] = array(
		 'title' => __( 'Longitude', 'cb-bookings' ),
			'type' => 'text',
			'desc_tip' => __( 'Enter the longitude of this charter location', 'cb-bookings' ),
			 'id'=>'cb_location_longitude_'.$x
		);

		$bookings_settings[] = array(
			'type'  => 'sectionend',
			'id'    => 'cb_bookings_location_'.$x.'_section',
		);

	}

	return $bookings_settings;
}

function cb_charter_schedule_settings($bookings_settings){
	$sunset_times = new CB_Sunset_Times();
	$sunset_message = (get_option('cb_sunset_api') == 'yes' && $sunset_times->number_sunsets_needed == 0)
		?	'Sunset API is enabled and sunset times are loaded.'
		: 'If enabled, the start time of sunset charters will be a calculated value based on the actual sunset time and duration of the charter. The charter will be scheduled to end exactly at civil twilight so that your boat will be sailing through the sunset and dock before pitch dark. <div class="cb-hold-sunset-initiation"></div>';


	$bookings_settings[] =
		array(
			'title'     => __( 'Charter Schedule', 'cb-bookings' ),
				'type'      => 'title',
				'desc'     => __( 'Global schedule options for charter booking products. Settings affect every charter booking product. ', 'woocommerce' ),
				'id'        => 'cb_bookings_global_section',
		);
		$bookings_settings[] = array(
			'title' => __( 'Open Days', 'cb-bookings' ),
			 'type' => 'multiselect',
			'desc_tip' => __( 'Which days of the week is your business open and offering charter bookings?', 'cb-bookings' ),
			'default'=>'',
			'class'=>'cb-admin-multiselect',
				'id'=>'cb_open_days',
				'options'=>array(
						'Mon'=>'Monday',
						'Tue'=>'Tuesday',
						'Wed'=>'Wednesday',
						'Thu'=>'Thursday',
						'Fri'=>'Friday',
						'Sat'=>'Saturday',
						'Sun'=>'Sunday'
				),
		 );
		 $bookings_settings[] =array(
			 'title' => __( 'Weeks In Advance', 'cb-bookings' ),
				'type' => 'text',
				'default' => '12',
				'desc_tip' => __( 'Max number of future weeks that are open for booking.  Enter -1 for no limit on advance bookings', 'cb-bookings' ),
				 'id'=>'cb_weeks_advance'
			);
		 $bookings_settings[] =array(
			 'title' => __( 'Durations (h)', 'cb-bookings' ),
				'type' => 'textarea',
				'default' => '2 | 4 | 6| 8',
				'desc_tip' => __( 'In hours, enter a list of durations separated by the pipe. Ex: 2 hour sailing charter or a 4 hour sailing charter', 'cb-bookings' ),
				 'id'=>'cb_durations'
			);
		//cb_same_day_buffer
		$bookings_settings[] =array(
			'title' => __( 'Buffer Between Charters (m)', 'cb-bookings' ),
			 'type' => 'number',
			 'default' => '30',
			 'desc_tip' => __( 'In minutes (1-120), please set a minimum number of minutes as a buffer between charters.', 'cb-bookings' ),
				'id'=>'cb_same_day_buffer'
		 );
		 /*
		$bookings_settings[] = array(
			'title' => 'Sunset API',
			'desc'     => __( $sunset_message ),
			 'type' => 'checkbox',
			 'class'=>'cb-enable-sunset',
			 'default' => 'yes',
			 'id'=>'cb_sunset_api'
		 );
*/
		$bookings_settings[] =		array(
					'type'  => 'sectionend',
					'id'    => 'cb_bookings_global_section',
				);
		return $bookings_settings;
}

function cb_blackout_dates($bookings_settings){
	$bookings_settings[] =		array(
				'title'     => __( 'Black Out Dates', 'cb-bookings' ),
					'type'      => 'title',
					'desc'     => __( 'Global black out dates. These black out dates should be used for times when every location, every charter is closed for bookings. (Ex. Christmas Holidays or seasonal closing)', 'woocommerce' ),
					'id'        => 'cb_bookings_blackout_section',
			);
	$bookings_settings[] = array(
		 'title' => __( 'Add Blackout Date Range', 'cb-bookings' ),
		 'type' => 'add_setting',
		 'desc_tip' => __( 'Add a start and end date for a blackout period', 'cb-bookings' ),
			 'id'=>'cb_add_blackout',
			 'css'=>'',
			 'name'=>'Add blackout',
			 'class'=>'button cb-add-blackout '
		);
	$bookings_settings[] =		array(
				'type'  => 'sectionend',
				'id'    => 'cb_bookings_blackout_section',
			);

	$num_blackouts = 	get_option('cb_number_blackouts');
	for($x = 1; $x <= $num_blackouts; $x++){
		$bookings_settings[] =		array(
					'title'     => __( 'Black Out '.$x, 'cb-bookings' ),
						'type'      => 'title',
						'id'        => 'cb_bookings_blackout_'.$x.'_section',
				);
			$bookings_settings[] =		array(
						'title' => __( 'Start Date', 'cb-bookings' ),
						 'type' => 'date',
						'desc_tip' => __( 'Enter the start date of black out period', 'cb-bookings' ),
						'name'=>'Start Date',
							'id'=>'cb_blackout_start_'.$x,
					 );

		 $bookings_settings[] =		array(
						'title' => __( 'End Date', 'cb-bookings' ),
						 'type' => 'date',
						'desc_tip' => __( 'Enter the end date of black out period', 'cb-bookings' ),
						'name'=>'End Date',
							'id'=>'cb_blackout_end_'.$x
					 );
		 $bookings_settings[] = array(
				 'title' => 'Remove',
				 'type' => 'add_setting',
				 'desc_tip' => 'Remove this blackout period',
					 'id'=>'cb_remove_blackout_'.$x,
					 'css'=>'',
					 'name'=>'Remove Black Out '.$x,
					 'value'=>'blackout_'.$x,
					 'class'=>'button cb-remove-blackout '
				);
		 $bookings_settings[] =		array(
						'type'  => 'sectionend',
						'id'    => 'cb_bookings_blackout_'.$x.'_section',
					);
		}



		return $bookings_settings;
}

// Add Settings for new section
function add_bookings_products_settings( $settings, $current_section ) {
	// make sure we're looking only at our section
	if ( 'cb_bookings' === $current_section ) {
		$bookings_settings = array();
		$bookings_settings = cb_location_settings($bookings_settings);
		//$bookings_settings = cb_charter_schedule_settings($bookings_settings);
		//$bookings_settings = cb_blackout_dates($bookings_settings);
		return $bookings_settings;

	} else {
		// otherwise give us back the other settings
		return $settings;
	}
}
add_filter( 'woocommerce_get_settings_products', __NAMESPACE__ . '\\add_bookings_products_settings', 10, 2 );

/**
 * Custom Settings Types
 * Add types within the WooCommerce Settings API
 *
 */


 /* A simple <hr> separater */
function cb_admin_field_cb_hr( $value ){
	$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
	$description = WC_Admin_Settings::get_field_description( $value );
	$content = '<tr valign="top"><td class="forminp forminp-cb-hr">
							<hr>
							</td></tr>';
	echo $content;
}
add_action( 'woocommerce_admin_field_cb_hr' , 'cb_admin_field_cb_hr' );

/* button which uses ajax to add an additional setting of specified type */
function chbk_admin_add_setting( $value ){
		        $option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
		        $description = WC_Admin_Settings::get_field_description( $value );
		        ?>
		        <tr valign="top">
		            <th scope="row" class="titledesc">
		                <label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
		                <?php echo  $description['tooltip_html'];?>
		            </th>

		            <td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
										 <input
		                        name ="<?php echo esc_attr( $value['name'] ); ?>"
		                        id   ="<?php echo esc_attr( $value['id'] ); ?>"
		                        type ="submit"
		                        value="<?php echo esc_attr( $value['name'] ); ?>"
		                        class="<?php echo esc_attr( $value['class'] ); ?>"
		                />
		                <?php echo $description['description']; ?>

		            </td>
		        </tr>

		   <?php
}
add_action( 'woocommerce_admin_field_add_setting' , __NAMESPACE__ . '\\chbk_admin_add_setting' );

/* date range with start and end date*/
function chbk_admin_date_range($value){
	$option_value = (array) WC_Admin_Settings::get_option( $value['id'] );
	$description = WC_Admin_Settings::get_field_description( $value );

	?>
	<tr valign="top">
			<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
					<?php echo  $description['tooltip_html'];?>

			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
					 <input

									id   ="<?php echo esc_attr( $value['id'] ); ?>_start"
									type ="text"
									class="datepicker cb-float-left <?php echo esc_attr( $value['class'] ); ?>"
					/>

				 <a class="cb-remove-blackout-date-0"><i class="fas fa-trash"></i></a>
			</td>
		</tr>
		<?php
}
add_action( 'woocommerce_admin_field_date_range' , __NAMESPACE__ . '\\chbk_admin_date_range' );

?>
