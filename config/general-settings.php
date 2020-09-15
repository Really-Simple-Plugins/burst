<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

$this->fields = $this->fields + array(

		'a_b_testing' => array(
			'source'   => 'settings',
			'step'     => 'general',
			'type'     => 'checkbox',
			'label'    => __( "Enable A/B testing", 'burst' ),
			'table'    => true,
			'disabled' => true,
			'default'  => false,
			//setting this to true will set it always to true, as the get_cookie settings will see an empty value
		),

		'a_b_testing_duration' => array(
			'source'    => 'settings',
			'step'      => 'general',
			'type'      => 'number',
			'label'     => __( "Duration in days of the A/B testing period",
				'burst' ),
			'table'     => true,
			'disabled'  => true,
			'condition' => array( 'a_b_testing' => true ),
			'default'   => 30,
		),

		'cookie_expiry' => array(
			'source'  => 'settings',
			'step'    => 'general',
			'type'    => 'number',
			'default' => 365,
			'label'   => __( "Cookie banner expiration in days",
				'burst' ),
			'table'   => true,
		),
	);
