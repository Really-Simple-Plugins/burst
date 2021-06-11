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
	$data = $request->get_json_params();

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

	//make sure it's set.
	if (!isset( $_COOKIE['burst_uid'])) {
		burst_setcookie('burst_uid', $burst_uid, BURST::$experimenting->cookie_expiration_days);
	}

	$default_data = array(
		'test_version' => false,
		'experiment_id' => false,
		'conversion' => false,
		'url' => '',
	);
	$data = wp_parse_args($data, $default_data);
	$url = sanitize_text_field($data['url']);
	$experiment_id = intval($data['experiment_id']);

	$time = time();
	$time_minus_threshold = strtotime("-30 minutes");

	global $wpdb;
	$update_array = array(
		'page_url'            		=> sanitize_text_field( $url ),
		'time'               		=> $time,
		'uid'               		=> sanitize_title($burst_uid),
		'test_version'				=> burst_sanitize_test_version($data['test_version']),
		'experiment_id'				=> $experiment_id,
	);

	// Only update conversion when it is true. Otherwise conversions will be deleted when the page has been revisited.
	if ($data['conversion'] == true){
		error_log('conversion');
		$update_array['conversion'] = intval($data['conversion']);
	} else {
		error_log('no conversion');
	}
	error_log(print_r($update_array, true));
	error_log('burst_track_hit');
	//check if the current users' uid/experiment id combination is already in the database.
	$prepare = $wpdb->prepare( "select `time` from {$wpdb->prefix}burst_statistics where experiment_id = %s and uid = %s order by time desc limit 1", $experiment_id, sanitize_title($burst_uid));
	$last_visit_time = $wpdb->get_var($prepare);
	// check if the last entry is smaller than the time_minus_threshold so that multiple visits will result in multiple entries and not just one. 
	if ($last_visit_time > 0 && $last_visit_time > $time_minus_threshold) {
		error_log('already in db in the last 30 minutes, so we update');
		$wpdb->update(
			$wpdb->prefix . 'burst_statistics',
			$update_array,
			array('time' => $last_visit_time)
		);
	} else {
		error_log('new entry');
		$wpdb->insert(
			$wpdb->prefix . 'burst_statistics',
			$update_array
		);
	}

	//check if we can stop this experiment.
	// $experiment = new BURST_EXPERIMENT($experiment_id);
	// if ( $time > $experiment->date_end ) {
	// 	$experiment->stop();
	// }


}