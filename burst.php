<?php
/**
 * Plugin Name: Burst | A/B Split Testing
 * Plugin URI: https://www.wordpress.org/plugins/burst
 * Description: A/B testing tool
 * Version: 1.0.6
 * Text Domain: burst
 * Domain Path: /languages
 * Author: Really Simple Plugins
 * Author URI: https://www.wpburst.com
 */

/*
    Copyright 2018  Burst BV  (email : support@wpburst.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
define( 'burst_free', true );

if ( ! function_exists( 'burst_activation_check' ) ) {
	/**
	 * Checks if the plugin can safely be activated, at least php 5.6 and wp 4.6
	 *
	 * @since 1.0.0
	 */
	function burst_activation_check() {
		if ( version_compare( PHP_VERSION, '5.6', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Burst cannot be activated. The plugin requires PHP 5.6 or higher',
				'burst' ) );
		}

		global $wp_version;
		if ( version_compare( $wp_version, '5.0', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'Burst cannot be activated. The plugin requires WordPress 5.0 or higher',
				'burst' ) );
		}
	}

	register_activation_hook( __FILE__, 'burst_activation_check' );
}

require_once( plugin_dir_path( __FILE__ ) . 'functions.php' );
if ( ! class_exists( 'BURST' ) ) {
	class BURST {
		public static $instance;
		public static $experimenting;
		public static $admin;
		public static $review;
		public static $field;
		public static $config;

		private function __construct() {
			self::setup_constants();
			self::includes();
			self::hooks();
			self::$experimenting  = new burst_experimenting();

			self::$config = new burst_config();

			if ( is_admin() ) {
				self::$review          = new burst_review();
				self::$admin           = new burst_admin();
				self::$field 		   = new burst_field();
			}

		}

		/**
		 * Setup constants for the plugin
		 */

		private function setup_constants() {

			require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$plugin_data = get_plugin_data( __FILE__ );

			define( 'burst_url', plugin_dir_url( __FILE__ ) );
			define( 'burst_path', plugin_dir_path( __FILE__ ) );
			define( 'burst_plugin', plugin_basename( __FILE__ ) );
			define( 'burst_plugin_name', 'Burst A/B Testing' );
			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '';
			define( 'burst_version', $plugin_data['Version'] . $debug );
			define( 'burst_plugin_file', __FILE__ );
			define( 'burst_MAIN_MENU_POSITION', 5 );
		}

		/**
		 * Instantiate the class.
		 *
		 * @return BURST
		 * @since 1.0.0
		 *
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance )
			     && ! ( self::$instance instanceof BURST )
			) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		private function includes() {

			if ( is_admin() ) {
				require_once( burst_path . 'class-admin.php' );
				require_once( burst_path . 'class-field.php');
				require_once( burst_path . 'grid/grid.php' );
				require_once( burst_path . 'class-review.php' );
			}

			require_once( burst_path . 'statistics/statistics.php' );
			require_once( burst_path . 'statistics/class-statistics.php' );

			require_once( burst_path . 'experiments/class-experiment.php' );
			require_once( burst_path . 'experiments/experimenting.php' );

			require_once( burst_path . 'rest-api/rest-api.php' );

			require_once( burst_path . 'config/class-config.php');
		}

		private function hooks() {
			//add early hooks
		}
	}

	/**
	 * Load the plugins main class.
	 */
	add_action(
		'plugins_loaded',
		function () {
			BURST::get_instance();
		},
		9
	);
}

if ( ! function_exists( 'burst_set_activation_time_stamp' ) ) {
	/**
	 * Set an activation time stamp
	 *
	 * @param $networkwide
	 */
	function burst_set_activation_time_stamp( $networkwide ) {
		update_option( 'burst_activation_time', time() );
	}

	register_activation_hook( __FILE__, 'burst_set_activation_time_stamp' );

}

if ( ! function_exists( 'burst_start_tour' ) ) {
	/**
	 * Start the tour of the plugin on activation
	 */
	function burst_start_tour() {
		if ( ! get_site_option( 'burst_tour_shown_once' ) ) {
			update_site_option( 'burst_tour_started', true );
		}
	}

	register_activation_hook( __FILE__, 'burst_start_tour' );
}

if ( ! function_exists( 'burst_add_admin_bar_item' ) ) {
	/**
	 * Add admin bar for displaying if a test is running on the page
	 *
	 */
	add_action( 'admin_bar_menu', 'burst_add_admin_bar_item', 500 );
	function burst_add_admin_bar_item ( WP_Admin_Bar $admin_bar ) {
	    if ( ! current_user_can( 'manage_options' ) ) {
	        return;
	    }
	    $test_running = false;
	    $active_experiments = burst_get_active_experiments_id();
	    $count = count($active_experiments);
	    $color = $count > 0 ? 'burst-green' : 'grey';
	    $icon = '<span class="burst-bullet '. $color .'"></span>';
	    $title =  burst_plugin_name;
	    if ( $count > 0 ) {
	    	$title .= ' | ' . sprintf( __( '%s active experiments', 'text_domain' ), $count );;
	    }

		wp_register_style( 'burst-admin-bar',
		trailingslashit( burst_url ) . 'assets/css/admin-bar.css', "",
		burst_version );
		wp_enqueue_style( 'burst-admin-bar' );

	    $admin_bar->add_menu( array(
	        'id'    	=> 'burst',
	        'parent' 	=> null,
	        'group'  	=> null,
	        'title' 	=> $icon . '<span class="burst-top-menu-text">'. $title .'</span>', //you can use img tag with image link. it will show the image icon Instead of the title.
	        'href'  	=> admin_url('admin.php?page=burst'),
	        'meta' 		=> [
	            		'title' => __( $title, 'burst' ), //This title will show on hover
	        ]
	    ) );

	    $admin_bar->add_menu(array(
			'id'     	=> 'burst-results',
			'parent' 	=> 'burst',
			'title'  	=> __( 'Dashboard', 'burst' ),
			'href'   	=> admin_url( 'admin.php?page=burst' ),
		) );

	    // if experiments are active display them here
	    if ($count > 0) {
	    	$admin_bar->add_menu(array(
				'id'     	=> 'burst-active-experiments',
				'parent' 	=> 'burst',
				'title'  	=> __( 'Active experiments', 'burst' ),
				'href'   	=> admin_url( 'admin.php?page=burst' ),
			) );

	    	// loop through active experiments and add to top menu
			foreach ($active_experiments as $experiment) {
		    	$admin_bar->add_menu(array(
					'id'     	=> 'burst-add-experiment-'. $experiment->ID,
					'parent' 	=> 'burst-active-experiments',
					'title'  	=> $experiment->title,
					'href'   	=> admin_url( 'admin.php?page=burst-experiments&id='. $experiment->ID .'&action=edit' ),
				) );
		    }
	    }

		$admin_bar->add_menu(array(
			'id'     	=> 'burst-add-experiment',
			'parent' 	=> 'burst',
			'title'  	=> __( 'Add experiment', 'burst' ),
			'href'   	=> admin_url( 'admin.php?page=burst-experiments&action=new' ),
		) );

		
	}

}