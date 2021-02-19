<?php defined('ABSPATH') or die("you do not have acces to this page!");

add_action('rest_api_init', 'burst_register_rest_routes');
function burst_register_rest_routes(){
	register_rest_route('burst/v1', 'hit', array(
        'methods' => 'POST',
        'callback' => 'burst_track_hit',
        'permission_callback' => '__return_true',
    ));	
}



/**
 * Add a new page visit to the database
 * @param WP_REST_Request $request
 * @return WP_REST_Response $response
 */

function burst_track_hit(WP_REST_Request $request){
	//check if this user has a cookie 
	$burst_uid = isset( $_COOKIE['burst_uid']) ? $_COOKIE['burst_uid'] : false;
	if ( !$burst_uid ) {
		// if user is logged in get burst meta user id
		if (is_user_logged_in()) {
			$burst_uid = get_user_meta(get_current_user_id(), 'burst_cookie_uid');
			//if no user meta is found, add new unique ID
			if (!isset($burst_uid)) {
				//generate random string
				$burst_uid = burst_random_str();
				update_user_meta(get_current_user_id(), 'burst_cookie_uid', $burst_uid);
			}
		} else {
			$burst_uid = burst_random_str();
		}
	}

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
		'page_id'                   => intval( url_to_postid( $url ) ),
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
}