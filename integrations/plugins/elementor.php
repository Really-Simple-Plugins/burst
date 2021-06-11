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
	if (Plugin::$instance->documents->get( $post_id )->is_built_with_elementor()) {
		$content = $el_frontend->get_builder_content_for_display( $post_id, false);
	} 
	return $content;
}

//add_filter('burst_the_content', 'burst_elementor_the_content', 10, 2);

/**
 * clear elementor cache when duplicating a page, so that a new elementor css file will be genrated
 * @param  boolean $post_id     
 * @param  boolean $new_post_id 
 * @return void             
 */

function burst_clear_elementor_cache($post_id = false, $new_post_id = false){
	Elementor\Plugin::$instance->files_manager->clear_cache();
}
//add_action('burst_after_duplicate_post', 'burst_clear_elementor_cache');

function wpse120996_add_custom_field_automatically($post_id) {
	error_log('publishing post');
	error_log($post_id);
	error_log("post status ".get_post_status($post_id));
	//we don't want our experiment post statuses published.
//	if ( get_post_status($post_id) === 'experiment' ) {
//		wp_update_post( array(
//			'id' => $post_id,
//			'post_status' => 'draft',
//			'hidden_post_status' => 'draft'
//		) );
//	}

}
add_action('publish_post', 'wpse120996_add_custom_field_automatically');

function wpse120996_save_post($post_id) {
	error_log('publishing post');
	error_log($post_id);
	error_log("post status ".get_post_status($post_id));
	//we don't want our experiment post statuses published.
//	if ( get_post_status($post_id) === 'experiment' ) {
//		wp_update_post( array(
//			'id' => $post_id,
//			'post_status' => 'draft',
//			'hidden_post_status' => 'draft'
//		) );
//	}

}
add_action('save_post', 'wpse120996_save_post');



