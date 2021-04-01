<?php
defined( 'ABSPATH' ) or die();

use Elementor\Plugin;

function burst_clear_elementor_cache($post_id = false, $new_post_id = false){
	Elementor\Plugin::$instance->files_manager->clear_cache();
}
add_action('burst_after_duplicate_post', 'burst_clear_elementor_cache');