<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_action( 'wp_ajax_burst_get_experiment_statistics', 'burst_get_experiment_statistics' );

function burst_get_experiment_statistics($experiment_id = false, $data = array('visits', 'conversions', 'conversionrate') ){
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	if (!$experiment_id) {
		error_log('No experiment id');
		$experiment_id = burst_get_active_experiment_id();
		error_log('experiment ID');
		error_log(print_r($experiment_id, true));
	}
	$experiment_id[0]->ID;
	$data = array(
	    'labels' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
	    'datasets' => array( array(
	        'data' =>[8, 13, 8, 9, 6, 0, false],
	        'backgroundColor' => 'rgba(231, 126, 35, 0.2)',
	        'borderColor' => 'rgba(231, 126, 35, 1)',
	        'label' => 'Original'
	    ),
	    array(
	        'data' => array(8, 9, 12, 20, 6, 2.5 ,3),
	        'backgroundColor' => 'rgba(51, 152, 219, 0.2)',
	        'borderColor' => 'rgba(51, 152, 219, 1)',
	        'label' => 'Variation'
	    ))
	);
	// // we will pass post IDs and titles to this array
	// $return = array();

	// // you can use WP_Query, query_posts() or get_posts() here - it doesn't matter
	// $search_results = new WP_Query( array( 
	// 	's'=> $_GET['q'], // the search query
	// 	'post_status' => 'publish', // if you don't want drafts to be returned
	// 	'ignore_sticky_posts' => 1,
	// 	'posts_per_page' => 50 // how much to show at once
	// ) );
	// if( $search_results->have_posts() ) :
	// 	while( $search_results->have_posts() ) : $search_results->the_post();	
	// 		// shorten the title a little
	// 		$title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
	// 		$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
	// 	endwhile;
	// endif;
	$return  = array(
		'success' => true,
		'message' => 'success',
		'data'    => $data,
	);
	echo json_encode( $return );
	error_log(print_r($return, true));
	die;
}
