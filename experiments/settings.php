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
				'help'        => __( 'This name is for internal use only. Try to give the experiment a clear name, so you can find this test again.', 'burst' ),
				'required' => true,
			),

			'duplicate_or_choose_existing' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'create',
				'type'               => 'radio',
				'label'              => '<span class="burst-experiment-dot variant">'. __( "Variant" , 'burst' ).'</span>',
				'options' => array(
					'duplicate'       => __( "Duplicate this page and edit", 'burst' ),
					'existing-page'  => __( "Choose existing page", 'burst' ),
				),
				'default' => 'duplicate',
				'required' => true,
			),

			'variant_id' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'create',
				'type'               => 'select2',
				'query_settings'	 => array(
						'post_type' 	=> burst_get_current_post_type(), //get_current_post_type();
						'post_status' 	=> burst_get_all_post_statuses( array('publish') ),
						'post__not_in' 	=> array( burst_get_current_post_id() ),
				),
				'help'               => __( 'Select the control page. The control page is the page you want to improve (or compare with another page).',
					'burst' ),
				'condition' => array(
					'duplicate_or_choose_existing' => 'existing-page',
				),
				'required' => true,
			),

			'goal' => array(
				'source'      => 'BURST_EXPERIMENT',
				'step'        => 'goal',
				'type'        => 'radio',
				'options' => array(
					'click'  => __( "Click on element", 'burst' ),
					'visit'  => __( "Page visit", 'burst' ),
				),
				'label'       => __( "Goal", 'burst' ),
				'default' => 'click-on-element',
				'help'        => __( 'Select what metric you want to improve. For example a click on a button or a visit on a checkout page.', 'burst' ),
				'required' => true,
			),

			'goal_identifier' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'goal',
				'type'               => 'text',
				'placeholder' => __( '.class or #id' ),
				'condition' => array(
					'goal' => 'click',
				),
				'required' => true,
			),

			'goal_id' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'goal',
				'type'               => 'select2',
				'query_settings'	 => array(
						'post_type' 	=> 'any', //get_current_post_type();
						'post_status' 	=> 'publish',
				),
				'condition' => array(
					'goal' => 'visit',
				),
				'required' => true,
			),

			'minimum_samplesize' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'timeline',
				'type'               => 'radio',
				'default'            => 384,
				'options' => array(
					'384'  => sprintf(__( "%s visits", 'burst' ), 384),
					'1000'  => sprintf(__( "%s visits", 'burst' ), 100),
					'5000'  => sprintf(__( "%s visits", 'burst' ), 5000),
					'-1'  => __( "Custom number of visits", 'burst' ),
				),
				'label'       => __( "Timeline", 'burst' ),
				'required' => true,
			),

			'minimum_samplesize_custom' => array(
				'source'             => 'BURST_EXPERIMENT',
				'step'               => 'timeline',
				'type'               => 'number',
				'minimum'            => 384,
				'condition' => array(
					'minimum_samplesize' => -1,
				),
				'required' => true,
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
