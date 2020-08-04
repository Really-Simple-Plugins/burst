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

