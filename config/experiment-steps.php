<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
$this->steps = array(
	'experiment' =>
		array(
            STEP_SELECT => array(
				"id"    => "select",
				"title" => __( "Create", 'burst' ),
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
                        'intro' => '<p>'. __('Burst will help you find the best settings for your experiment. When we gather enough data we will recommend the best settings for the experiments you create.', 'burst') .'</p>',
                    ),
                ),
			),
			STEP_START    => array(
				"id"    => "start",
				"title" => __( "Start", 'burst' ),
			),
		),
);
