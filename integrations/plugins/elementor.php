<?php
defined( 'ABSPATH' ) or die();

use Elementor\Plugin;

function burst_clear_elementor_cache($post_id, $new_post_id){
	Plugin::$instance->files_manager->clear_cache();
}
add_action('burst_after_duplicate_post', 'burst_clear_elementor_cache');
