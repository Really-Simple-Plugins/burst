<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_filter('burst_fields', 'burst_add_experiment_settings');
function burst_add_experiment_settings($fields){

	$fields = $fields + array(

			'title' => array(
				'source'      => 'BURST_EXPERIMENT',
				'step'        => 'general',
				'type'        => 'text',
				'label'       => __( "Experiment name", 'burst' ),
				'placeholder' => __( 'For example: Red vs green buttons' ),
				//'help'        => __( 'This name is for internal use only. Try to give the experiment a clear name, so you can find this test again.', 'burst' ),
			),

			'duplicate_or_choose_existing' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'create',
				'type'               => 'radio',
				'label'              => '<span class="burst-experiment-dot variant">'. __( "Variant" . '</span>',
					'burst' ),
				'options' => array(
					'duplicate'       => __( "Duplicate this page and edit", 'burst' ),
					'existing-page'  => __( "Choose existing page", 'burst' ),
				),
				'default' => 'duplicate',
			),

			'variant_id' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'create',
				'type'               => 'select2',
				'query_settings'	 => array(
						'post_type' 	=> burst_get_current_post_type(), //get_current_post_type();
						'post_status' 	=> 'experiment',
						'exclude' 		=> burst_get_current_post_id(),
				),
				'help'               => __( 'Select the control page. The control page is the page you want to improve (or compare with another page).',
					'burst' ),
				'condition' => array(
					'duplicate_or_choose_existing' => 'existing-page',
				),
			),

			// 'control_id' => array(
			// 	'source'             => 'BURST_EXPERIMENT',
			// 	'step'               => 'variant',
			// 	'type'               => 'select2variant',
			// 	'label'              => __( "Variant",
			// 		'burst' ),
			// 	'help'               => __( 'Select the control page. The control page is the page you want to improve (or compare with another page).',
			// 		'burst' ),
			// ),

			// 'variant_id' => array(
			// 	'source'             => 'BURST_EXPERIMENT',
			// 	'step'               => 'general',
			// 	'type'               => 'select2',
			// 	'label'              => __( "Variant",
			// 		'burst' ),
			// 	'help'               => __( 'Select or make a variant page. The variant page is the page you want to test against your control page. The variant page should be an improvement compared to the control page. At least you should think it is an improvement. That is something you will find out by running the experiment.',
			// 		'burst' ),
			// ),


			'goal' => array(
				'source'      => 'BURST_EXPERIMENT',
				'step'        => 'goal',
				'type'        => 'radio',
				'options' => array(
					'click-on-element'  => __( "Click on element", 'burst' ),
					'page-visit'  => __( "Page visit", 'burst' ),
				),
				'label'       => __( "Goal", 'burst' ),
				'default' => 'click-on-element',
			),

			'goal_element_id_or_class' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'goal',
				'type'               => 'text',
				'placeholder' => __( '.class or #id' ),
				'condition' => array(
					'goal' => 'click-on-element',
				),
			),

			'goal_url' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'goal',
				'type'               => 'select2',
				'query_settings'	 => array(
						'post_type' 	=> any, //get_current_post_type();
						'post_status' 	=> 'publish',
				),
				'condition' => array(
					'goal' => 'page-visit',
				),
			),

			'timeline_select' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'timeline',
				'type'               => 'radio',
				'options' => array(
					'7'  => __( "7 days", 'burst' ),
					'14'  => __( "14 days", 'burst' ),
					'28'  => __( "28 days (recommended)", 'burst' ),
					'custom'  => __( "Custom number of days", 'burst' ),
				),
				'label'       => __( "Timeline", 'burst' ),
				'default' => '28',
			),

			'timeline_custom' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'timeline',
				'type'               => 'number',
				'condition' => array(
					'timeline_select' => 'custom',
				),
			),



			// 'percentage_included' => array(
			// 	'source'      => 'BURST_EXPERIMENT',
			// 	'step'        => 'setup',
			// 	'type'        => 'weightslider',
			// 	'default'	  => '100',
			// 	'label'       => __( "Experiment weight", 'burst' ),
			// 	'placeholder' => __( 'Percentage in numbers' ),
			// 	'help'        => __( 'For internal use only', 'burst' ),
			// ),

		);


	return $fields;
}
