<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "burst_config" ) ) {

	class burst_config {
		private static $_this;
		public $fields = array();

		public $sections;
		public $pages;
		public $warning_types;
		public $yes_no;
		public $premium_experimenting;


		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;


			//common options type
			$this->yes_no = array(
				'yes' => __( 'Yes', 'burst' ),
				'no'  => __( 'No', 'burst' ),
			);

			$this->premium_experimenting
				= sprintf( __( "If you want to run a/b testing to track which banner gets the highest acceptance ratio, %sget premium%s.",
					'burst' ),
					'<a href="https://burst.io" target="_blank">', '</a>' )
				  . "&nbsp;";


			/* config files */
			require_once( burst_path . '/experiments/settings.php' );
			require_once( burst_path . '/config/general-settings.php' );
			

			if ( file_exists( burst_path . '/pro/config/' ) ) {
				// require_once( burst_path . '/pro/config/steps.php' );
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


		public function fields( $source = false, $step = false ) {

			$output = array();
			$fields = $this->fields;
			if ( $source ) {
				$fields = burst_array_filter_multidimensional( $this->fields, 'source', $source );
			}

			foreach ( $fields as $fieldname => $field ) {
				if ( $step ) {
					if ( ( $field['step'] == $step )
					     || ( is_array( $field['step'] )
					          && in_array( $step, $field['step'] ) )
					) {
						$output[ $fieldname ] = $field;
					}
				}
				if ( ! $step ) {
					$output[ $fieldname ] = $field;
				}

			}

			return $output;
		}

		public function init() {
			$this->fields = apply_filters('burst_fields', array() );
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
