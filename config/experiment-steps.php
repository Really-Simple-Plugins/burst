<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
$this->steps = array(
	'experiment' =>
		array(
            STEP_SELECT => array(
				"id"    => "select",
				"title" => __( "Setup", 'burst' ),
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
                    ),
                ),
			),
			STEP_START    => array(
				"id"    => "start",
				"title" => __( "Start", 'burst' ),
				'intro' =>
					'<h1>' . _x( "Get ready to finish your configuration.",
						'intro menu', 'burst' ) . '</h1>' .
					'<p>'
					. _x( "Generate your documents, then you can add them to your menu directly or do it manually after the wizard is finished.",
						'intro menu', 'burst' ) . '</p>',
			),
		),
);
