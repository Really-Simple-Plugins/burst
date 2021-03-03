<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_filter('burst_fields', 'burst_add_general_settings');
function burst_add_general_settings($fields){
	$fields = $fields + array(

		'clear_data_on_uninstall' => array(
            'source'   => 'settings',
			'step'     => 'general',
			'type'    => 'checkbox',
			'label'   => __( "Clear all data from Burst on uninstall",
				'burst' ),
			'default' => false,
			'tooltip'    => __( 'Enabling this option will delete all your settings, and the Burst tables when you deactivate and remove Burst.',
				'burst' ),
			'table'   => true,
		),
	);
	return $fields;
}
