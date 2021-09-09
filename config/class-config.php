<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "burst_config" ) ) {

	class burst_config {
		private static $_this;
		public $fields = array();
        public $steps = array();
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
            require_once( burst_path . '/config/general-settings.php' );
			require_once( burst_path . '/config/experiment-settings.php' );
            require_once( burst_path . '/config/experiment-steps.php' );


			if ( file_exists( burst_path . '/pro/config/' ) ) {
				// require_once( burst_path . '/pro/config/steps.php' );
			}
			/**
			 * The integrations are loaded with priority 10
			 * Because we want to initialize after that, we use 15 here
			 */
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

        public function preload_init(){
            $this->fields = apply_filters( 'burst_fields_load_types', $this->fields );
        }

		public function init() {
            $this->steps = apply_filters('burst_steps', $this->steps );
			$this->fields = apply_filters('burst_fields', $this->fields );
		}

        public function has_sections( $page, $step ) {
            if ( isset( $this->steps[ $page ][ $step ]["sections"] ) ) {
                return true;
            }

            return false;
        }

	}

} //class closure
