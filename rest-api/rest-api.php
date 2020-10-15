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

	// if user is logged in get burst meta user id
	if (user_is_logged_in()) {
		get_user_meta(get_current_user_id(), 'burst_cookie_uid')
	}

	//check if this user has a cookie 
	$user_id = isset( $_COOKIE['burst_uid']) ? $_COOKIE['burst_uid'] : false;
	if ( !$user_id ) {
		$key = uniqid('', true));
		//generate uid 
		//@todo get random string 
		$user_id = ;
	}
	

	setcookie('burst_uid', $user_id, time() + apply_filters('burst_cookie_retention', DAY_IN_SECONDS * 365), '/');

	$url = $request->get_body();
	
	$statistics = new BURST_STATISTICS($url);

	$statistics->page_id = get_queried_object_id();

	$statistics->save();

	error_log(print_r($statistics, true));

}