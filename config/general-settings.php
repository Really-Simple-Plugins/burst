<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_filter('burst_fields', 'burst_add_general_settings');
function burst_add_general_settings($fields){
	$fields = $fields + array(
		'display_clear_data_on_uninstall_popup' => array(
            'source'   => 'settings',
			'step'     => 'general',
			'type'    => 'checkbox',
			'label'   => __( "Delete all data on deactivate", 'burst' ),
			'default' => false,
			'tooltip'    => __( 'Enabling this option will delete all your settings, statistics and experiments when you deactivate this plugin',
				'burst' ),
			'table'   => true,
		),
		'use_url_parameter' => array(
            'source'   => 'settings',
			'step'     => 'general',
			'type'    => 'checkbox',
			'label'   => __( "Use URL Parameter to enable caching", 'burst' ),
			'default' => false,
			'tooltip'    => __( 'Enabling this option will add a non-identifieable string (a questionmark + 11 random characters) to the URL of an experiment. If you use a caching plugin, we recommend you to enable this option.',
				'burst' ),
			'table'   => true,
		),
	);
	return $fields;
}
