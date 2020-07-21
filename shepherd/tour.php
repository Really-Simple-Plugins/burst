<?php
if ( ! defined( 'ABSPATH' ) ) {
	die( 'you do not have access to this page!' );
}

class burst_tour {

	private static $_this;

	public $capability = 'activate_plugins';
	public $url;
	public $version;

	function __construct() {
		if ( isset( self::$_this ) ) {
			wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
				get_class( $this ) ) );
		}

		self::$_this = $this;

		$this->url     = burst_url . '/shepherd';
		$this->version = burst_version;
		add_action( 'wp_ajax_burst_cancel_tour',
			array( $this, 'listen_for_cancel_tour' ) );
		add_action( 'admin_init', array( $this, 'restart_tour' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	static function this() {
		return self::$_this;
	}

	public function enqueue_assets( $hook ) {

		if ( get_site_option( 'burst_tour_started' ) ) {
			if ( $hook !== 'plugins.php'
			     && ( strpos( $hook, 'burst' ) === false )
			) {
				return;
			}

			wp_register_script( 'burst-tether',
				trailingslashit( $this->url )
				. 'tether/tether.min.js', "", $this->version );
			wp_enqueue_script( 'burst-tether' );

			wp_register_script( 'burst-shepherd',
				trailingslashit( $this->url )
				. 'tether-shepherd/shepherd.min.js', "", $this->version );
			wp_enqueue_script( 'burst-shepherd' );

			wp_register_style( 'burst-shepherd',
				trailingslashit( $this->url )
				. "css/shepherd-theme-arrows.min.css", "",
				$this->version );
			wp_enqueue_style( 'burst-shepherd' );

			wp_register_style( 'burst-shepherd-tour',
				trailingslashit( $this->url ) . "css/burst-tour.min.css", "",
				$this->version );
			wp_enqueue_style( 'burst-shepherd-tour' );

			wp_register_script( 'burst-shepherd-tour',
				trailingslashit( $this->url )
				. '/js/burst-tour.js', array( 'jquery' ), $this->version );
			wp_enqueue_script( 'burst-shepherd-tour' );

			$logo
				   = '<span class="burst-tour-logo"><img class="burst-tour-logo" style="width: 70px; height: 70px;" src="'
				     . burst_url . 'assets/images/icon-256x256.png"></span>';
			$html  = '<div class="burst-tour-logo-text">' . $logo
			         . '<span class="burst-tour-text">{content}</span></div>';
			$steps = array(
				0 => array(
					'title'  => __( 'Welcome to Burst', 'burst' ),
					'text'   => __( "Get ready for privacy legislation around the world. Follow a quick tour or start configuring the plugin!",
						'burst' ),
					'link'   => admin_url( "admin.php?page=burst" ),
					'attach' => '.burst-settings-link',
				),
				2 => array(
					'title'  => __( 'Dashboard', 'burst' ),
					'text'   => __( "This is your Dashboard. When the Wizard is completed, this will give you an overview of tasks, tools, and documentation.",
						'burst' ),
					'link'   => add_query_arg( array(
						"page" => "burst-wizard",
						"step" => STEP_COOKIES
					), admin_url( "admin.php" ) ),
					'attach' => '.burst-dashboard-title',
				),
				3 => array(
					'title'  => __( "The Wizard", "burst" ),
					'text'   => __( "This is where you configure your website for your specific region. It includes everything you need to get started. We will come back to the Wizard soon.",
						'burst' ),
					'link'   => add_query_arg( array(
						'page' => 'burst-cookiebanner',
						'id'   => burst_get_default_banner_id()
					), admin_url( "admin.php" ) ),
					'attach' => '.burst-menu-item',
				),
				4 => array(
					'title'  => __( 'Cookie Banner', 'burst' ),
					'text'   => __( "Here you can configure and style your cookie banner if the Wizard is completed. An extra tab will be added with region-specific settings.",
						'burst' ),
					'link'   => admin_url( "admin.php?page=burst-script-center" ),
					'attach' => '.burst-tablinks [bottom right]',
				),

				5 => array(
					'title'  => __( "Integrations", "burst" ),
					'text'   => __( "Based on your answers in the Wizard, we will automatically enable integrations with relevant services and plugins. In case you want to block extra scripts, you can add them to the Script Center.",
						'burst' ),
					'link'   => admin_url( "admin.php?page=burst-settings" ),
					'attach' => '.burst-tablinks [bottom right]',
				),
				6 => array(
					'title'  => __( 'Settings', 'burst' ),
					'text'   => __( "Adding Document CSS, disabling certain features, and other settings can be found here. You can also revisit the tour here.",
						'burst' ),
					'link'   => admin_url( "admin.php?page=burst-proof-of-consent" ),
					'attach' => '.burst-cookie_expiry',
				),
				7 => array(
					'title'  => __( 'Proof of Consent', 'burst' ),
					'text'   => __( "Complianz tracks changes in your Cookie Notice and Cookie Policy with time-stamped documents. This is your consent registration while respecting the data minimization guidelines and won't store any user data.",
						'burst' ),
					'link'   => admin_url( "admin.php?page=burst-wizard" ),
					'attach' => '#burst-cookiestatement-snapshot-filter',
				),
				8 => array(
					'title'  => __( "Let's start the Wizard",
						'burst' ),
					'text'   => __( "You are ready to start the Wizard. For more information, FAQ, and support, please visit Complianz.io.",
						'burst' ),
					'attach' => '.burst-menu-item',
				),

			);
			$steps = apply_filters( 'burst_shepherd_steps', $steps );
			wp_localize_script( 'burst-shepherd-tour', 'burst_tour',
				array(
					'ajaxurl'        => admin_url( 'admin-ajax.php' ),
					'html'           => $html,
					'token'          => wp_create_nonce( 'burst_tour_nonce' ),
					'nextBtnText'    => __( "Next", "burst" ),
					'backBtnText'    => __( "Previous", "burst" ),
					'configure'      => __( "Configure", "burst" ),
					'configure_link' => admin_url( "admin.php?page=burst-wizard" ),
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
