<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;
use \WC_Admin_Settings;

// Add a new section to WooCommerce > Settings > Products
function bookings_products_section( $sections ) {
	$sections['cb_bookings'] = __( 'Charter Bookings', 'cb-bookings' );
	return $sections;
}
add_filter( 'woocommerce_get_sections_products', __NAMESPACE__ . '\\bookings_products_section' );


function cb_location_settings($bookings_settings){
	
	$bookings_settings[] = array(
		'type'  => 'sectionend',
		'id'    => 'cb_bookings_location_section',
	);

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
		//'fields'=>'all_with_meta'
	);
	$users = get_users($args);
	//echo '<pre>'; var_dump($users); echo '</pre>';
	$options = array();
	foreach($users as $user){
		$options[$user->ID]=$user->display_name;
	}
	$bookings_settings[] = array(
		'title' => 'Captain',
		 'type' => 'select',
		'desc_tip' => 'User must be at least a shop manager to be included in the options',
		'default'=>'',
			'id'=>'cb_captain',
			'options'=>$options,
	 );

	 $bookings_settings[] = array(
 	 'title' => 'Capacity',
 		'type' => 'number',
 		'desc_tip' => 'Maximum number of guests allowed on board (integer required)',
 		 'id'=>'cb_booking_capacity'
 	);

	$bookings_settings[] = array(
	'title' => 'Open Weather API Key',
	'type'=>'text',
	'desc_tip' => 'Copy and Paste Your Open Weather API Key here.',
	'id'=>'cb_open_weather_key'
 );

	$bookings_settings[] = array(
		'title' => 'Temperature Units',
		'type' => 'select',
		'desc_tip' => 'Display temperature in F or C?',
		'default'=>'farenheight',
		'class'=>'cb-temp-units',
			'id'=>'cb_temp_units',
			'options'=>array(
					'celcius'=>'Celcius',
					'farenheight'=>'Farenheight'
			),
	);

	$bookings_settings[] = array(
		'title' => 'Wind Units',
			'type' => 'select',
		'desc_tip' => 'Display temperature in MPH or KPH?',
		'default'=>'mph',
		'class'=>'cb-wind-units',
			'id'=>'cb_wind_units',
			'options'=>array(
					'mph'=>'MPH',
					'kph'=>'KPH'
			),
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
	
//to add support for locations: get_option('cb_number_locations')
	for($x = 1; $x <= 1; $x++){
		$titlenum = $x + 1;
		
		$bookings_settings[] =	array(
			'title'     => __( 'Location ', 'cb-bookings' ),
				'type'      => 'title',
				'id'        => 'cb_bookings_location_'.$x.'_section',
		);

		$bookings_settings[] = array(
			'title' => 'Region',
			   'type' => 'text',
			   'desc_tip' => 'Charter region (ex: Outer Banks, NC)',
				'id'=>'cb_location_region_'.$x
		   );

		   
		$bookings_settings[] = array(
		 'title' => 'Name',
			'type' => 'text',
			'desc_tip' => 'Name to appear at checkout',
			 'id'=>'cb_location_name_'.$x
		);

		$bookings_settings[] = array(
		 'title' => 'Address',
			'type' => 'text',
			'desc_tip' => 'Format: Street, City, State, Zip, Country',
			 'id'=>'cb_location_address_'.$x
		);
		$bookings_settings[] = array(
		 'title' => 'Latitude',
			'type' => 'text',
			'desc_tip' => 'Enter the latitude of this charter location',
			 'id'=>'cb_location_latitude_'.$x
		);
		$bookings_settings[] = array(
		 'title' => 'Longitude',
			'type' => 'text',
			'desc_tip' => 'Enter the longitude of this charter location',
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

	$bookings_settings[] =
		array(
			'title'     => __( 'Charter Schedule', 'cb-bookings' ),
				'type'      => 'title',
				'desc'     => __( 'Global schedule options for charter booking products. Settings affect every charter booking product. ', 'woocommerce' ),
				'id'        => 'cb_bookings_global_section',
		);
		$bookings_settings[] = array(
			'title' => 'Open Days',
			 'type' => 'multiselect',
			'desc_tip' => 'Which days of the week is your business open and offering charter bookings?',
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
			 'title' => 'Weeks In Advance',
				'type' => 'text',
				'default' => '12',
				'desc_tip' => 'Max number of future weeks that are open for booking.  Enter -1 for no limit on advance bookings',
				 'id'=>'cb_weeks_advance'
			);
		 $bookings_settings[] =array(
			 'title' => 'Durations (h)',
				'type' => 'textarea',
				'default' => '2 | 4 | 6| 8',
				'desc_tip' => 'In hours, enter a list of durations separated by the pipe. Ex: 2 hour sailing charter or a 4 hour sailing charter',
				 'id'=>'cb_durations'
			);
		//cb_same_day_buffer
		$bookings_settings[] =array(
			'title' => 'Buffer Between Charters (m)',
			 'type' => 'number',
			 'default' => '30',
			 'desc_tip' => 'In minutes (1-120), please set a minimum number of minutes as a buffer between charters.',
				'id'=>'cb_same_day_buffer'
		 );
		

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
		 'title' => 'Add Blackout Date Range',
		 'type' => 'add_setting',
		 'desc_tip' => 'Add a start and end date for a blackout period',
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
						'title' => 'Start Date',
						 'type' => 'date',
						'desc_tip' => 'Enter the start date of black out period',
						'name'=>'Start Date',
							'id'=>'cb_blackout_start_'.$x,
					 );

		 $bookings_settings[] =		array(
						'title' => 'End Date',
						 'type' => 'date',
						'desc_tip' => 'Enter the end date of black out period',
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

function cb_pro_display_settings($bookings_settings){

	$bookings_settings[] =		array(
				'title'     => __( 'Display Settings', 'cb-bookings' ),
					'type'      => 'title',
					'desc'     => __( 'Settings that affect the display of charter booking pages and shortcodes, etc. If you add a slug for the tabs, all of the content from the slugs indicated will be pulled into tabs on the charter booking pages. This puts the right information in front of the customer at time of booking.', 'woocommerce' ),
					'id'        => 'cb_bookings_display_section',
			);
	$bookings_settings[] =array(
				'title' => 'WooCommerce Tabs: Terms',
				 'type' => 'text',
				 'default' => '',
				 'desc_tip' => 'Copy and paste the slug of charter terms and conditions page. This is not the website terms, but the special terms for all charters (ex: you can bring  your own beer and we provide a cooler).',
					'id'=>'cb_terms_slug'
			 );
	$bookings_settings[] =array(
		 				'title' => 'WooCommerce Tabs: FAQs',
		 				 'type' => 'text',
		 				 'default' => '',
		 				 'desc_tip' => 'Copy and paste the slug of charter FAQs page - the frequently asked questions for all charters (ex: Can I bring beer? Are Kids allowed?)',
		 					'id'=>'cb_faqs_slug'
		 			 );

	$bookings_settings[] =		array(
				 'type'  => 'sectionend',
				 'id'    => 'cb_bookings_display_section',
			 );

	return $bookings_settings;
}


// Add Settings for new section
function add_bookings_products_settings( $settings, $current_section ) {
	//$license = new MSP_License();
	// make sure we're looking only at our section
	if ( 'cb_bookings' === $current_section ) {
		
			$bookings_settings = array();
			$bookings_settings = cb_location_settings($bookings_settings);
			//$bookings_settings = cb_charter_schedule_settings($bookings_settings);
			//$bookings_settings = cb_blackout_dates($bookings_settings);
			//$bookings_settings = cb_pro_display_settings($bookings_settings);
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
add_action( 'woocommerce_admin_field_cb_hr' , __NAMESPACE__ . '\\cb_admin_field_cb_hr' );

/* button which uses ajax to add an additional setting of specified type */
function cb_admin_add_setting( $value ){
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
add_action( 'woocommerce_admin_field_add_setting' , __NAMESPACE__ . '\\cb_admin_add_setting' );

/* date range with start and end date*/
function cb_admin_date_range($value){
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
add_action( 'woocommerce_admin_field_date_range' , __NAMESPACE__ . '\\cb_admin_date_range' );

?>
