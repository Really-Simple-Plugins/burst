<?php
defined( 'ABSPATH' ) or die();

/**
  Schedule cron jobs if useCron is true
  Else start the functions.
*/
add_action( 'plugins_loaded', 'burst_schedule_cron' );
function burst_schedule_cron() {
	$useCron = false;
	if ( $useCron ) {
		if ( ! wp_next_scheduled( 'burst_every_day_hook' ) ) {
			wp_schedule_event( time(), 'burst_daily', 'burst_every_day_hook' );
		}
		add_action( 'burst_every_day_hook', array( BURST::$experimenting, 'maybe_activate_winner' ) );
	} else {
		add_action( 'init', array( BURST::$experimenting, 'maybe_activate_winner' ), 100 );
	}
}

add_filter( 'cron_schedules', 'burst_filter_cron_schedules' );
function burst_filter_cron_schedules( $schedules ) {
	$schedules['burst_weekly']  = array(
		'interval' => WEEK_IN_SECONDS,
		'display'  => __( 'Once every week' )
	);
	$schedules['burst_daily']   = array(
		'interval' => DAY_IN_SECONDS,
		'display'  => __( 'Once every day' )
	);

	return $schedules;
}








