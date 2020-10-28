<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_filter('burst_fields', 'burst_add_ab_test_settings');
function burst_add_ab_test_settings($fields){

	$fields = $fields + array(

			'name' => array(
				'source'      => 'BURST_AB_TEST',
				'step'        => 'general',
				'type'        => 'text',
				'label'       => __( "Descriptive name for your experiment", 'burst' ),
				'placeholder' => __( 'For example: Red vs green buttons' ),
				'help'        => __( 'This name is for internal use only. Try to give the experiment a clear name, so you can find this test again.', 'burst' ),
			),

			'control_id' => array(
				'source'             => 'BURST_AB_TEST',
				'step'               => 'general',
				'type'               => 'select2',
				'label'              => __( "Control",
					'burst' ),
				'help'               => __( 'Select the control page. The control page is the page you want to improve (or compare with another page).',
					'burst' ),
			),

			// 'variant_id' => array(
			// 	'source'             => 'BURST_AB_TEST',
			// 	'step'               => 'general',
			// 	'type'               => 'select2',
			// 	'label'              => __( "Variant",
			// 		'burst' ),
			// 	'help'               => __( 'Select or make a variant page. The variant page is the page you want to test against your control page. The variant page should be an improvement compared to the control page. At least you should think it is an improvement. That is something you will find out by running the experiment.',
			// 		'burst' ),
			// ),


			'kpi' => array(
				'source'      => 'BURST_AB_TEST',
				'step'        => 'goals',
				'type'        => 'radio',
				'options' => array(
					'sale'       => __( "Sale", 'burst' ),
					'click-on-element'  => __( "Click on element", 'burst' ),
					'click-through-rate'  => __( "Click through rate", 'burst' ),
					'form-submission'  => __( "Form submission", 'burst' ),
				),
				'label'       => __( "Key Performance Indicator", 'burst' ),
				'placeholder' => __( 'Descriptive title of the AB test' ),
				'help'        => __( 'For internal use only', 'burst' ),
			),

		);


	return $fields;
}
