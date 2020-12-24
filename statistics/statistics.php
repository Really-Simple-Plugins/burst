<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_action( 'wp_ajax_burst_get_experiment_statistics', 'burst_get_experiment_statistics' );
/**
 * Function for getting statistics for display with Chart JS
 * @param  integer $experiment_id   Experiment ID, if no experiment ID is found get active experiments
 * @param  array   $data   			Select the data you want to display
 * @return json                     Returns a JSON that is compatible with Chart JS
 *
 * @todo  Real data should be displayed here
 */
function burst_get_experiment_statistics($experiment_id = false, $data = array('visits', 'conversions', 'conversionrate') ){
	if ( ! burst_user_can_manage() ) {
		return;
	}
	if (!$experiment_id) {
		error_log('No experiment id');
		$experiment_id = burst_get_active_experiments_id();
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
/**
 * Get the latest visit for a UID for a specific page. 
 * Specify a data_variable if you just want the result for a specific parameter
 * 
 * @param  integer $burst_uid     The Burst UID which is saved in a cookie 
 *                                (and in the user meta if the user is logged in)
 * @param  string  $page_url      The page URL you want the latest visit from
 * @param  string  $data_variable Specify which data you want, if left empty you'll 
 *                                get an object with everything
 * @return object                 Returns the latest visit data        
 */
function burst_get_latest_visit_data($burst_uid = false, $page_url = false, $data_variable = false){
	error_log('burst_get_latest_visit');
	if (!$burst_uid && !$page_url) {
		return false; 
	}
	$sql = "";
	if ($page_url) {
		$sql = " AND page_url ='" . esc_attr($page_url) . "' ";

	}

	global $wpdb;
	if ($burst_uid) {
		$statistics
		= $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}burst_statistics where uid = %s". $sql ." ORDER BY time DESC LIMIT 1 ",
		esc_attr( $burst_uid) ) );
	}
	error_log('stats');
	error_log(print_r($statistics, true));
	if (empty($statistics)){
		return false;
	} else {

		if ($data_variable) {
			return $statistics[0]->$data_variable;
		} else {
			return $statistics;	
		}
		
	}	
	
}
/**
 * Function to get the current URL used in the load_experiment_content function
 * @return url The current URL
 */
function burst_get_current_url() {
        return parse_url(get_permalink(), PHP_URL_PATH);
    }
