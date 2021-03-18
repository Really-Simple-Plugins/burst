<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}


$general_settings = get_option('burst_options_settings');
if (isset($general_settings['clear_data_on_uninstall']) && $general_settings['clear_data_on_uninstall']) {

	$options = array(
		'burst_activation_time',
	);


	foreach ($options as $option_name) {
		delete_option($option_name);
		delete_site_option($option_name);
	}

	global $wpdb;
	$table_names = array(
		//$wpdb->prefix . 'burst_statistics',
	);

	foreach($table_names as $table_name){
		$sql = "DROP TABLE IF EXISTS $table_name";
		$wpdb->query($sql);
	}



}