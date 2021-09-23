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

defined( 'ABSPATH' ) or die();
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
		public static $statistics;
		public static $admin;
		public static $review;
		public static $field;
		public static $config;
		public static $tour;
		public static $notices;

		private function __construct() {
			self::setup_constants();
			self::includes();
			self::hooks();
			self::$experimenting  = new burst_experimenting();
			self::$statistics  = new burst_statistics();

			self::$config = new burst_config();

			if ( is_admin() ) {
				self::$review          = new burst_review();
				self::$admin           = new burst_admin();
				self::$field 		   = new burst_field();
				self::$tour            = new burst_tour();
				self::$notices         = new burst_notices();
			}
		}

		/**
		 * Setup constants for the plugin
		 */

		private function setup_constants() {
			define( 'burst_url', plugin_dir_url( __FILE__ ) );
			define( 'burst_path', plugin_dir_path( __FILE__ ) );
			define( 'burst_plugin', plugin_basename( __FILE__ ) );
			define( 'burst_plugin_name', 'Burst' );
			$debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : '';
			define( 'burst_version', '1.0.1' . $debug );
			define( 'burst_plugin_file', __FILE__ );
			define( 'burst_main_menu_position', 40 );
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
			require_once( burst_path . 'integrations/integrations.php');
			
			if ( is_admin() ) {
				require_once( burst_path . 'class-admin.php' );
				require_once( burst_path . 'class-field.php');
				require_once( burst_path . 'grid/grid.php' );
				require_once( burst_path . 'class-review.php' );
				require_once( burst_path . 'shepherd/tour.php' );
				require_once( burst_path . 'config/notices.php' );
			}
			require_once( burst_path . 'statistics/class-statistics.php' );
			require_once( burst_path . 'experiments/class-experiment.php' );
			require_once( burst_path . 'experiments/experimenting.php' );
			require_once( burst_path . 'rest-api/rest-api.php' );
			require_once( burst_path . 'config/class-config.php');
			require_once( burst_path . 'cron/cron.php');
		}

		private function hooks() {
			$plugin = plugin_basename(__FILE__);

			/**
			 * Tell the consent API we're following the api
			 */
			add_filter("wp_consent_api_registered_$plugin", function(){return true;});
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
		$experiment_posts = get_posts(
			array(
				'post_status' => 'draft',
				'meta_key'    => 'burst_deactivated_experiment',
				'meta_value'  => true,
			)
		);
		foreach ($experiment_posts as $experiment_post ) {
			delete_post_meta($experiment_post->ID, 'burst_deactivated_experiment' );
			$args = array(
				'ID'            => $experiment_post->ID,
				'post_status'   => 'experiment',
			);
			wp_update_post($args);
		}
	}

	register_activation_hook( __FILE__, 'burst_start_tour' );
}

if ( !function_exists( 'burst_clear_scheduled_hooks' )) {
	register_deactivation_hook( __FILE__, 'burst_clear_scheduled_hooks' );
	function burst_clear_scheduled_hooks() {
		wp_clear_scheduled_hook( 'burst_every_week_hook' );
		wp_clear_scheduled_hook( 'burst_every_day_hook' );

		$experiment_posts = get_posts(
			array(
				'post_status' => 'experiment',
			)
		);
		foreach ($experiment_posts as $experiment_post ) {
			//add some meta data to signify that this is an experiment post
			update_post_meta($experiment_post->ID, 'burst_deactivated_experiment', true);
			$args = array(
				'ID'             => $experiment_post->ID,
				'post_status' => 'draft',
			);
			wp_update_post($args);
		}
	}
}


