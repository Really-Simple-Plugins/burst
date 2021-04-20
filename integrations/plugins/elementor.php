<?php
defined( 'ABSPATH' ) or die();

use Elementor\Plugin;

/**
 * If page is built with Elementor, get the Elementor content 
 * @param  string $content [description]
 * @param  int $post_id [description]
 * @return string          [description]
 */
function burst_elementor_the_content($content, $post_id){
	$el_frontend = new Elementor\Frontend();
	$el_plugin = \Elementor\Plugin::instance();
	if ($el_plugin->db->is_built_with_elementor( $post_id )) {
		$content = $el_frontend->get_builder_content_for_display( $post_id, false);
	} 
	return $content;
}

add_filter('burst_the_content', 'burst_elementor_the_content', 10, 2);

/**
 * clear elementor cache when duplicating a page, so that a new elementor css file will be genrated
 * @param  boolean $post_id     
 * @param  boolean $new_post_id 
 * @return void             
 */

function burst_clear_elementor_cache($post_id = false, $new_post_id = false){
	Elementor\Plugin::$instance->files_manager->clear_cache();
}
add_action('burst_after_duplicate_post', 'burst_clear_elementor_cache');