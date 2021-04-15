<?php defined('ABSPATH') or die("you do not have acces to this page!");

add_action('rest_api_init', 'burst_register_rest_routes');
function burst_register_rest_routes(){
	register_rest_route('burst/v1', 'hit', array(
        'methods' => 'POST',
        'callback' => 'burst_track_hit',
        'permission_callback' => '__return_true',
    ));
	register_rest_route('burst/v1', 'uid', array(
		'methods' => 'GET',
		'callback' => 'burst_rest_api_get_uid',
		'permission_callback' => '__return_true',
	));
}

function burst_rest_api_get_uid(WP_REST_Request $request){
	$response = json_encode( array(
		'uid' => burst_get_uid(),
	) );
	header( "Content-Type: application/json" );
	echo $response;
	exit;
}

/**
 * Add a new page visit to the database
 * @param WP_REST_Request $request
 * @return WP_REST_Response $response
 */

function burst_track_hit(WP_REST_Request $request){
	//check if this user has a cookie 
	$burst_uid = burst_get_uid();

	burst_setcookie('burst_uid', $burst_uid, 1);
	$data = $request->get_json_params();

	$default_data = array(
		'test_version' => 'control',
		'experiment_id' => false,
		'conversion' => false,
		'url' => '',
	);
	$data = wp_parse_args($data, $default_data);
	$url = sanitize_text_field($data['url']);
	$experiment_id = intval($data['experiment_id']);

	global $wpdb;
	$update_array = array(
		'page_url'            		=> sanitize_text_field( $url ),
		'time'               		=> time(),
		'uid'               		=> sanitize_title($burst_uid),
		'test_version'				=> burst_sanitize_test_version($data['test_version']),
		'experiment_id'				=> $experiment_id,
		'conversion'				=> intval($data['conversion']),
	);

	//check if the current users' uid/experiment id combination is already in the database.
	$id = $wpdb->get_var( $wpdb->prepare( "select ID from {$wpdb->prefix}burst_statistics where experiment_id = %s and uid = %s", $experiment_id, sanitize_title($burst_uid) ) );
	if ($id) {
		$wpdb->update(
			$wpdb->prefix . 'burst_statistics',
			$update_array,
			array('ID' => $id)
		);
	} else {
		$wpdb->insert(
			$wpdb->prefix . 'burst_statistics',
			$update_array
		);
	}

	//check if we can stop this experiment.
	$experiment = new BURST_EXPERIMENT($experiment_id);
	if ( time() > $experiment->date_end ) {
		$experiment->stop();
	}


}