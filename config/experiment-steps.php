<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
$this->steps = array(
	'experiment' =>
		array(
            STEP_SELECT => array(
				"id"    => "select",
				"title" => __( "Create", 'burst' ),
//                'sections' => array(
//                    1 => array(
//                        'title' => __( 'Select control and variant', 'burst' ),
//                    ),
//                ),
			),

			STEP_METRICS => array(
				"title"    => __( "Metrics", 'burst' ),
				"id"       => "metrics",
                'sections' => array(
                    1 => array(
                        'title' => __( 'Define goals', 'burst' ),
                    ),
                    2 => array(
                        'title' => __( 'Duration and significance', 'burst' ),
                        'intro' => '<p>'. __('Burst will help you find the best settings for your experiment. Fast experimenting comes at the cost of less accurate data. But obtaining accurate data is time consuming. Choose your battles wisely! ;) ', 'burst') .'</p>',
                    ),
                ),
			),
			STEP_START    => array(
				"id"    => "start",
				"title" => __( "Start", 'burst' ),
			),
		),
);
