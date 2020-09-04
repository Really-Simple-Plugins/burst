<?php
/**
 * Plugin Name: Burst | A/B Split Testing
 * Plugin URI: https://www.wordpress.org/plugins/burst
 * Description: A/B testing tool
 * Version: 1.0.3
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
		public static $ab_tester;
		public static $admin;
		public static $review;
		public static $field;
		public static $config;

		private function __construct() {
			self::setup_constants();
			self::includes();
			self::hooks();
			self::$ab_tester  = new burst_ab_test();

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
				require_once( burst_path . 'class-database.php' );
				require_once( burst_path . 'class-field.php');
			}

			require_once( burst_path . 'class-review.php' );
			require_once( burst_path . 'ab-tests/class-ab-test.php' );
			require_once( burst_path . 'ab-tests/ab-test.php' );

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