<?php
namespace Charter_Bookings;
use \Datetime;
use \DateTimeZone;
use \DateInterval;

/**
 * =======================================
 * DEACTIVATION HOOK
 * functions to be called on de-activation
 * =======================================
 */

 register_deactivation_hook( __FILE__, __NAMESPACE__ . '\\cb_deactivate' );
 function cb_deactivate(){
 	cb_cancel_cron_notifications();
 }

 function cb_cancel_cron_notifications (){
	 $cb_notifications = array(
 		'cb_send_customer_reminders',
 		'cb_send_admin_reminders',
		'cb_cron_schedule_reminders_hook',
		'cboatbk_load_sunsets_hook'
 	);
	foreach($cb_notifications as $cb_notification){
		wp_clear_scheduled_hook($cb_notification);
	}
 }

 /**
  * =======================================
  * ON PLUGIN DELETION
  * functions to be called on PLUGIN DELETION - i.e. purge all data
  * =======================================
  */

 ?>
