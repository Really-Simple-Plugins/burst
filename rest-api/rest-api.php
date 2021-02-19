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
	error_log('track hit');

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

	//we need to check if this post is a goal for an experiment. the goal_url.
	$url = burst_get_current_url();
	//look up in the database
	$experiment = new BURST_EXPERIMENT(false, false, $url);
	if ($experiment->id){
		$experiment->conversion = true;
		$experiment->save();
	}

	$default_data = array(
		'test_version' => 'control',
		'experiment_id' => false,
		'conversion' => false,
		'url' => '',
	);
	$data = wp_parse_args($data, $default_data);
	$url = sanitize_text_field($data['url']);

	$statistics = new BURST_STATISTICS();
	$statistics->uid = $burst_uid;
	$statistics->page_url = $url;
	$statistics->page_id = url_to_postid($url);
	$statistics->test_version = $data['test_version'];
	$statistics->experiment_id = $data['experiment_id'];
	$statistics->conversion = $data['conversion'];
	$statistics->track();
}