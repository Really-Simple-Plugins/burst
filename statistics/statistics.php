<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_action( 'wp_ajax_burst_get_experiment_statistics', 'burst_get_experiment_statistics' );

function burst_get_experiment_statistics($id = false, $data = array('visits', 'conversions', 'conversionrate') ){

	$data = [
	    'labels' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'],
	    'datasets' => [[
	        'data' =>[8, 7, 8, 9, 6],
	        'backgroundColor' => '#f2b21a',
	        'borderColor' => '#e5801d',
	        'label' => 'Legend'
	    ]]
	];


	echo json_encode( $data );
	die;
}