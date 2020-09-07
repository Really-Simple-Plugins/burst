<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "burst_config" ) ) {

	class burst_config {
		private static $_this;
		public $fields = array();


		/**
		 * Some scripts need to be loaded in specific order
		 * key: script or part of script to wait for
		 * value: script or part of script that should wait
		 * */

		/**
		 * example:
		 *
		 *
		 * add_filter('burst_dependencies', 'my_dependency');
		 * function my_dependency($deps){
		 * $deps['wait-for-this-script'] = 'script-that-should-wait';
		 * return $deps;
		 * }
		 */
		public $dependencies = array();

		/**
		 * placeholders for not iframes
		 * */

		public $placeholder_markers = array();

		/**
		 * Scripts with this string in the source or in the content of the script tags get blocked.
		 *
		 * */

		public $script_tags = array();

		/**
		 * Style strings (google fonts have been removed in favor of plugin recommendation)
		 * */

		public $style_tags = array();

		/**
		 * Scripts in this list are loaded with post scribe.js
		 * due to the implementation, these should also be added to the list above
		 *
		 * */

		public $async_list = array();

		public $iframe_tags = array();
		public $iframe_tags_not_including = array();


		/**
		 * images with a URl in this list will get blocked
		 * */

		public $image_tags = array();

		public $amp_tags
			= array(
				'amp-ad-exit',
				'amp-ad',
				'amp-analytics',
				'amp-auto-ads',
				'amp-call-tracking',
				'amp-experiment',
				'amp-pixel',
				'amp-sticky-ad',
				// Dynamic content.
				'amp-google-document-embed',
				'amp-gist',
				// Media.
				'amp-brightcove',
				'amp-dailymotion',
				'amp-hulu',
				'amp-soundcloud',
				'amp-vimeo',
				'amp-youtube',
				'amp-iframe',
				// Social.
				'amp-addthis',
				'amp-beopinion',
				'amp-facebook-comments',
				'amp-facebook-like',
				'amp-facebook-page',
				'amp-facebook',
				'amp-gfycat',
				'amp-instagram',
				'amp-pinterest',
				'amp-reddit',
				'amp-riddle-quiz',
				'amp-social-share',
				'amp-twitter',
				'amp-vine',
				'amp-vk',
			);

		public $sections;
		public $pages;
		public $warning_types;
		public $yes_no;
		public $countries;
		public $purposes;
		public $details_per_purpose_us;
		public $regions;
		public $eu_countries;
		public $premium_geo_ip;
		public $premium_ab_testing;
		public $collected_info_children;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;

			/**
			 * The legal version is only updated when document contents or the questions leading to it are changed
			 * 1: start version
			 * 2: introduction of US privacy questions
			 * 3: new questions
			 * 4: new questions
			 * 5: UK as separate region
			 * 6: CA as separate region
			 * 7: Impressum in germany
			 * */
			define( 'BURST_LEGAL_VERSION', '7' );

			//common options type
			$this->yes_no = array(
				'yes' => __( 'Yes', 'burst' ),
				'no'  => __( 'No', 'burst' ),
			);

			$this->premium_geo_ip
				= sprintf( __( "To enable the warning only for countries with a cookie law, %sget premium%s.",
					'burst' ),
					'<a href="https://burst.io" target="_blank">', '</a>' )
				  . "&nbsp;";
			$this->premium_ab_testing
				= sprintf( __( "If you want to run a/b testing to track which banner gets the highest acceptance ratio, %sget premium%s.",
					'burst' ),
					'<a href="https://burst.io" target="_blank">', '</a>' )
				  . "&nbsp;";


			/* config files */
			require_once( burst_path . '/ab-tests/settings.php' );
			require_once( burst_path . '/config/general-settings.php' );
			

			if ( file_exists( burst_path . '/pro/config/' ) ) {
				// require_once( burst_path . '/pro/config/steps.php' );
				// require_once( burst_path . '/pro/config/questions-wizard.php' );
				// require_once( burst_path . '/pro/config/documents/documents.php' );
				// require_once( burst_path . '/pro/config/EU/questions-dataleak.php' );
				// require_once( burst_path . '/pro/config/US/questions-dataleak.php' );
				// require_once( burst_path . '/pro/config/CA/questions-dataleak.php' );
				// require_once( burst_path . '/pro/config/UK/questions-dataleak.php' );
				// require_once( burst_path . '/pro/config/EU/questions-processing.php' );
				// require_once( burst_path . '/pro/config/US/questions-processing.php' );
				// require_once( burst_path . '/pro/config/CA/questions-processing.php' );
				// require_once( burst_path . '/pro/config/UK/questions-processing.php' );
				// require_once( burst_path . '/pro/config/dynamic-fields.php' );
				// require_once( burst_path . '/pro/config/dynamic-document-elements.php' );
				// require_once( burst_path . '/pro/config/documents/US/dataleak-report.php' );
				// require_once( burst_path . '/pro/config/documents/US/privacy-policy.php' );
				// require_once( burst_path . '/pro/config/documents/US/processing-agreement.php' );
				// require_once( burst_path . '/pro/config/documents/US/privacy-policy-children.php' );
				// require_once( burst_path . '/pro/config/documents/CA/dataleak-report.php' );
				// require_once( burst_path . '/pro/config/documents/CA/privacy-policy.php' );
				// require_once( burst_path . '/pro/config/documents/CA/processing-agreement.php' );
				// require_once( burst_path . '/pro/config/documents/CA/privacy-policy-children.php' );
				// require_once( burst_path . '/pro/config/documents/disclaimer.php' );
				// require_once( burst_path . '/pro/config/documents/EU/privacy-policy.php' );
				// require_once( burst_path . '/pro/config/documents/EU/processing-agreement.php' );
				// require_once( burst_path . '/pro/config/documents/EU/dataleak-report.php' );
				// require_once( burst_path . '/pro/config/documents/EU/impressum.php' );
				// require_once( burst_path . '/pro/config/documents/UK/privacy-policy.php' );
				// require_once( burst_path . '/pro/config/documents/UK/processing-agreement.php' );
				// require_once( burst_path . '/pro/config/documents/UK/dataleak-report.php' );
				// require_once( burst_path . '/pro/config/documents/UK/privacy-policy-children.php' );
			}
			/**
			 * The integrations are loaded with priority 10
			 * Because we want to initialize after that, we use 15 here
			 */
			add_action( 'plugins_loaded', array( $this, 'load_warning_types' ),  );
			add_action( 'plugins_loaded', array( $this, 'init' ), 15 );
		}

		static function this() {
			return self::$_this;
		}


		public function get_section_by_id( $id ) {

			$steps = $this->steps['wizard'];
			foreach ( $steps as $step ) {
				if ( ! isset( $step['sections'] ) ) {
					continue;
				}
				$sections = $step['sections'];

				//because the step arrays start with one instead of 0, we increase with one
				return array_search( $id, array_column( $sections, 'id' ) ) + 1;
			}

		}

		public function get_step_by_id( $id ) {
			$steps = $this->steps['wizard'];

			//because the step arrays start with one instead of 0, we increase with one
			return array_search( $id, array_column( $steps, 'id' ) ) + 1;
		}


		public function fields(
			$page = false, $step = false, $section = false,
			$get_by_fieldname = false
		) {

			$output = array();
			$fields = $this->fields;
			if ( $page ) {
				$fields = burst_array_filter_multidimensional( $this->fields,
					'source', $page );
			}

			foreach ( $fields as $fieldname => $field ) {
				if ( $get_by_fieldname && $fieldname !== $get_by_fieldname ) {
					continue;
				}

				if ( $step ) {
					if ( $section && isset( $field['section'] ) ) {
						if ( ( $field['step'] == $step
						       || ( is_array( $field['step'] )
						            && in_array( $step, $field['step'] ) ) )
						     && ( $field['section'] == $section )
						) {
							$output[ $fieldname ] = $field;
						}
					} else {
						if ( ( $field['step'] == $step )
						     || ( is_array( $field['step'] )
						          && in_array( $step, $field['step'] ) )
						) {
							$output[ $fieldname ] = $field;
						}
					}
				}
				if ( ! $step ) {
					$output[ $fieldname ] = $field;
				}

			}

			return $output;
		}

		public function has_sections( $page, $step ) {
			if ( isset( $this->steps[ $page ][ $step ]["sections"] ) ) {
				return true;
			}

			return false;
		}

		public function init() {

		}


		public function load_warning_types() {
			$this->warning_types = apply_filters('burst_warning_types' ,array(
				'burst-feature-update' => array(
					'type'        => 'general',
					'label_error' => __( 'The Burst plugin has new features. Please check the wizard to see if all your settings are still up to date.',
						'burst' ),
				),
			)
		);
		}


	}



} //class closure
