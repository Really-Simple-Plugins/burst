<?php
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

class burst_tour {
	private static $_this;
	public $capability = 'activate_plugins';

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.', get_class( $this ) ) );
		}

		self::$_this = $this;
		add_action( 'wp_ajax_burst_cancel_tour', array( $this, 'listen_for_cancel_tour' ) );
		add_action( 'admin_init', array( $this, 'restart_tour' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	static function this() {
		return self::$_this;
	}

	/**
	 * Enqueue our assets
	 * @param $hook
	 */
	public function enqueue_assets( $hook ) {
		if ( get_site_option( 'burst_tour_started' ) ) {

			wp_register_script( 'burst-tether', burst_url . 'shepherd/tether/tether.min.js', "", burst_version );
			wp_enqueue_script( 'burst-tether' );
			wp_register_script( 'burst-shepherd', burst_url . 'shepherd/tether-shepherd/shepherd.min.js', "", burst_version );
			wp_enqueue_script( 'burst-shepherd' );
			wp_register_style( 'burst-shepherd', burst_url . "shepherd/css/shepherd-theme-arrows.min.css", "", burst_version );
			wp_enqueue_style( 'burst-shepherd' );
			wp_register_style( 'burst-shepherd-tour', burst_url . "shepherd/css/tour.min.css", "", burst_version );
			wp_enqueue_style( 'burst-shepherd-tour' );
			wp_register_script( 'burst-shepherd-tour', burst_url . 'shepherd/js/tour.js', array( 'jquery' ), burst_version );
			wp_enqueue_script( 'burst-shepherd-tour' );

			$logo  = '<span class="burst-tour-logo"><img class="burst-tour-logo" style="width: 70px; height: 70px;" src="' . burst_url . 'assets/images/burst-logo.svg"></span>';
			$html  = '<div class="burst-tour-logo-text">' . $logo . '<span class="burst-tour-text">{content}</span></div>';
			$steps = array(
				array(
					'title'  => __( 'Welcome to Burst', 'burst' ),
					'text'   => __( "Get ready for A/B testing. Follow a quick tour or start configuring the plugin!", 'burst' ),
					'link'   => admin_url( "plugins.php" ),
					'attach' => '.burst-settings-link',
					'position' => 'right',
				),
				array(
					'title'  => __( 'Dashboard', 'burst' ),
					'text'   => __( "This is your Dashboard. This will give you an overview of tasks, tools, and documentation.", 'burst' ),
					'link'   => admin_url( "admin.php?page=burst" ),
					'attach' => '.table-overview .burst-grid-title',
					'position' => 'right',
				),
//				array(
//					'title'  => __( "The Wizard", "burst" ),
//					'text'   => __( "This is where you configure your website for your specific region. It includes everything you need to get started. We will come back to the Wizard soon.", 'burst' ),
//					'link'   => add_query_arg( array( "page" => "burst-wizard", "step" => STEP_COOKIES ), admin_url( "admin.php" ) ),
//					'attach' => '.use_cdb_api .burst-label',
//					'position' => 'bottom',
//				),
//				array(
//					'title'  => __( 'Cookie Banner', 'burst' ),
//					'text'   => __( "Here you can configure and style your cookie banner if the Wizard is completed. An extra tab will be added with region-specific settings.", 'burst' ),
//					'link'   => add_query_arg( array( 'page' => 'burst-cookiebanner', 'id'   => burst_get_default_banner_id() ), admin_url( "admin.php" ) ),
//					'attach' => '#burst_COOKIEBANNER-general .burst-settings-title',
//					'position' => 'bottom',
//				),
//
//				array(
//					'title'  => __( "Integrations", "burst" ),
//					'text'   => __( "Based on your answers in the Wizard, we will automatically enable integrations with relevant services and plugins. In case you want to block extra scripts, you can add them to the Script Center.", 'burst' ),
//					'link'   => add_query_arg(array("page" => 'burst-script-center'), admin_url( "admin.php" ) ),
//					'attach' => '#integrations-services .burst-settings-title',
//					'position' => 'right',
//				),
//
//				array(
//					'title'  => __( 'Settings', 'burst' ),
//					'text'   => __( "Adding Document CSS, enabling safe mode, and other settings can be found here. You can also revisit the tour here.", 'burst' ),
//					'link'   => add_query_arg(array("page" => 'burst-settings'), admin_url( "admin.php" ) ),
//					'attach' => '#settings-general .burst-settings-title',
//					'position' => 'right',
//				),
			);


			$steps = apply_filters( 'burst_shepherd_steps', $steps );
			wp_localize_script( 'burst-shepherd-tour', 'burst_tour',
				array(
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'html'           => $html,
					'token'          => wp_create_nonce( 'burst_tour_nonce' ),
					'nextBtnText'    => __( "Next", "burst" ),
					'backBtnText'    => __( "Previous", "burst" ),
					'configure_text' => __( "Configure", "burst" ),
					'configure_link' => admin_url( "admin.php?page=burst" ),
					'startTour'      => __( "Start tour", "burst" ),
					'endTour'        => __( "End tour", "burst" ),
					'steps'          => $steps,
				) );

		}
	}

	/**
	 *
	 * @since 1.0
	 *
	 * When the tour is cancelled, a post will be sent. Listen for post and update tour cancelled option.
	 *
	 */

	public function listen_for_cancel_tour() {
		if ( ! isset( $_POST['token'] )
		     || ! wp_verify_nonce( $_POST['token'], 'burst_tour_nonce' )
		) {
			return;
		}
		update_site_option( 'burst_tour_started', false );
		update_site_option( 'burst_tour_shown_once', true );
	}

	/**
	 * Restart the tour
	 */
	public function restart_tour() {
		if ( ! isset( $_POST['burst_restart_tour'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_POST['burst_nonce'] )
		     || ! wp_verify_nonce( $_POST['burst_nonce'], 'burst_save' )
		) {
			return;
		}

		update_site_option( 'burst_tour_started', true );
		wp_redirect( admin_url( 'plugins.php' ) );
		exit;
	}

}
