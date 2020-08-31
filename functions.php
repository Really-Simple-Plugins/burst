<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! function_exists( 'burst_uses_google_analytics' ) ) {

	/**
	 * Check if site uses google analytics
	 * @return bool
	 */

	function burst_uses_google_analytics() {
		return BURST::$cookie_admin->uses_google_analytics();
	}
}

if ( ! function_exists( 'burst_user_can_manage' ) ) {
	function burst_user_can_manage() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'burst_get_ab_tests' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param array $args
	 *
	 * @return stdClass Object
	 */

	function burst_get_ab_tests( $args = array() ) {
		$args = wp_parse_args( $args, array( 'status' => 'active' ) );
		$sql  = '';
		global $wpdb;
		if ( $args['status'] === 'archived' ) {
			$sql = 'AND cdb.archived = true';
		}
		if ( $args['status'] === 'active' ) {
			$sql = 'AND cdb.archived = false';
		}

		$ab_tests
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_ab_tests as cdb where 1=1 $sql" );

		return $ab_tests;
	}
}

if ( ! function_exists( 'burst_get_ab_tests_by' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param string     $field The field to retrieve the user with. id | ID | title | variant_id | control_id | date_created
	 * @param int|string $value A value for $field. ID | title | date
	 *	 *
	 * @return stdClass Object
	 */

	function burst_get_ab_tests_by( $field, $value ) {
		global $wpdb;

		$ab_tests
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_ab_tests as cdb where {$field} = {$value}" );

		return $ab_tests;
	}
}