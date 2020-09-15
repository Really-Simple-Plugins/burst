<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_filter('burst_fields', 'burst_add_ab_test_settings');
function burst_add_ab_test_settings($fields){

	$fields = $fields + array(

			'title' => array(
				'source'      => 'BURST_AB_TEST',
				'step'        => 'general',
				'type'        => 'text',
				'label'       => __( "AB test title", 'burst' ),
				'placeholder' => __( 'Descriptive title of the AB test' ),
				'help'        => __( 'For internal use only', 'burst' ),
				'cols'     => 12,
			),

			'test_running' => array(
				'source'             => 'BURST_AB_TEST',
				'step'               => 'general',
				'type'               => 'checkbox',
				'label'              => __( "Test running",
					'burst' ),
				// 'help'               => __( 'When enabled, this is the cookie banner that is used for all visitors. Enabling it will disable this setting on the current default banner. Disabling it will enable randomly a different default banner.',
				// 	'burst' ),

				'default'            => false,
				//setting this to true will set it always to true, as the get_cookie settings will see an empty value
				//'callback_condition' => 'burst_ab_testing_enabled',

				'cols'     => 12,
			),

			'kpi' => array(
				'source'      => 'BURST_AB_TEST',
				'step'        => 'general',
				'type'        => 'text',
				'label'       => __( "Key Performance Indicator", 'burst' ),
				'placeholder' => __( 'Descriptive title of the AB test' ),
				'help'        => __( 'For internal use only', 'burst' ),
				'condition' =>	array('test_running' => true),
				'cols'     => 12,
			),

		);


	return $fields;
}
