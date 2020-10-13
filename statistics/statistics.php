<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
if(!is_admin()) {
  add_action('wp_footer', 'burst_register_page_visit');
}


function burst_register_page_visit(){
	global $wp;
	$url = $wp->request ? $wp->request : 'home';
	
	$statistics = new BURST_STATISTICS($url);

	$statistics->page_id = get_queried_object_id();

	$statistics->save();

	error_log(print_r($statistics, true));
}