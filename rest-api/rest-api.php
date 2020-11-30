<?php defined('ABSPATH') or die("you do not have acces to this page!");

add_action('rest_api_init', 'burst_register_rest_routes');
function burst_register_rest_routes(){
	register_rest_route('burst/v1', 'hit', array(
        'methods' => 'POST',
        'callback' => 'burst_track_hit',
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
	

	setcookie('burst_uid', $burst_uid, time() + apply_filters('burst_cookie_retention', DAY_IN_SECONDS * 365), '/');

	$data = $request->get_json_params();
	$url = $data['url'];
	$test_version = $data['test_version'];

	error_log('data');
	error_log(print_r($data, true));
	
	$statistics = new BURST_STATISTICS($url, $burst_uid);

	$statistics->page_id = url_to_postid($url);

	$statistics->test_version = $test_version;

	$statistics->save();

	error_log(print_r($statistics, true));

}