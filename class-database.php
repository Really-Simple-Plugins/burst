<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

/*
 * Install ab_test table
 * */

add_action( 'plugins_loaded', 'burst_install_ab_tests_table', 10 );
function burst_install_ab_tests_table() {
	if ( get_option( 'burst_cbdb_version' ) !== burst_version ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'burst_ab_tests';
		$sql        = "CREATE TABLE $table_name (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `archived` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `variant_id` int(11) NOT NULL,
            `control_id` int(11) NOT NULL,
            `test_running` boolean NOT NULL,
            `date_created` timestamp NOT NULL,
            `date_modified` timestamp NOT NULL,
            `date_started` timestamp NOT NULL,
            `date_end` timestamp NOT NULL,
            `kpi` text NOT NULL,
            `statistics` text NOT NULL,

              PRIMARY KEY  (ID)
            ) $charset_collate;";
		dbDelta( $sql );
		update_option( 'burst_cbdb_version', burst_version );

	}
}

if ( ! class_exists( "burst_ab_test" ) ) {
	class BURST_AB_TEST {
		public $id = false;
		public $archived = false;
		public $title;
		public $variant_id = false;
		public $control_id = false;
		public $test_running = false;
		public $date_created = false;
		public $date_modified = false;
		public $date_started = false;
		public $date_end = false;
		public $kpi = false;
		public $statistics = false;

		function __construct( $id = false, $set_defaults = true ) {

			$this->translation_id = $this->get_translation_id();
			$this->id = $id;

			if ( $this->id !== false ) {
				//initialize the ab_test settings with this id.
				$this->get();
			}

		}





		/**
		 * Add a new ab_test database entry
		 */

		private function add() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}
			$array = array(
				'title' => __( 'New AB test', 'burst' )
			);

			global $wpdb;

			$wpdb->insert(
				$wpdb->prefix . 'burst_ab_tests',
				$array
			);
			$this->id = $wpdb->insert_id;

		}


		public function process_form( $post ) {

			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			if ( ! isset( $post['burst_nonce'] ) ) {
				return false;
			}

			//check nonce
			if ( ! isset( $post['burst_nonce'] )
			     || ! wp_verify_nonce( $post['burst_nonce'],
					'burst_save_ab_test' )
			) {
				return false;
			}

			foreach ( $this as $property => $value ) {
				if ( isset( $post[ 'burst_' . $property ] ) ) {
					$this->{$property} = $post[ 'burst_' . $property ];
				}
			}

			$this->save();
		}

		/**
		 * Load the ab_test data
		 *
		 */

		private function get() {
			global $wpdb;

			if ( ! intval( $this->id ) > 0 ) {
				return;
			}

			$ab_tests
				= $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}burst_ab_tests where ID = %s",
				intval( $this->id ) ) );

			if ( isset( $ab_tests[0] ) ) {
				$ab_test         = $ab_tests[0];
				$this->archived       = $ab_test->archived;
				$this->title          = $ab_test->title;
				$this->$variant_id = $ab_test->variant_id;
				$this->$control_id = $ab_test->control_id;
				$this->$test_running = $ab_test->test_running;
				$this->$date_created = $ab_test->date_created;
				$this->$date_modified = $ab_test->date_modified;
				$this->$date_started = $ab_test->date_started;
				$this->$date_end = $ab_test->date_end;
				$this->$kpi = $ab_test->kpi;
				$this->$statistics = $ab_test->statistics;



		

				/**
				 * Fallback if upgrade didn't complete successfully
				 */

				// if ( $this->set_defaults ) {
				// 	if ($this->use_categories === true ) {
				// 		$this->use_categories = 'legacy';
				// 	} elseif ( $this->use_categories === false ) {
				// 		$this->use_categories = 'no';
				// 	}
				// 	if ($this->use_categories_optinstats  === true) {
				// 		$this->use_categories_optinstats = 'legacy';
				// 	} elseif ( $this->use_categories_optinstats === false ) {
				// 		$this->use_categories_optinstats = 'no';
				// 	}
				// }

			}

		}

		/**
		 * Check if this field is translatable
		 *
		 * @param $fieldname
		 *
		 * @return bool
		 */

		private function translate( $value, $fieldname ) {
			$key = $this->translation_id;

			if ( function_exists( 'pll__' ) ) {
				$value = pll__( $value );
			}

			if ( function_exists( 'icl_translate' ) ) {
				$value = icl_translate( 'burst', $fieldname . $key,
					$value );
			}

			$value = apply_filters( 'wpml_translate_single_string', $value,
				'burst', $fieldname . $key );

			return $value;

		}

		private function register_translation( $string, $fieldname ) {
			$key = $this->translation_id;
			//polylang
			if ( function_exists( "pll_register_string" ) ) {
				pll_register_string( $fieldname . $key, $string, 'burst' );
			}

			//wpml
			if ( function_exists( 'icl_register_string' ) ) {
				icl_register_string( 'burst', $fieldname . $key, $string );
			}

			do_action( 'wpml_register_single_string', 'burst', $fieldname,
				$string );

		}

		/**
		 * Get a prefix for translation registration
		 * For backward compatibility we don't use a key when only one banner, or when the lowest.
		 * If we don't use this, all field names from each banner will be the same, registering won't work.
		 *
		 * @return string
		 */

		public function get_translation_id() {
			//if this is the banner with the lowest ID's, no ID
			global $wpdb;
			$lowest = $wpdb->get_var( "select min(ID) from {$wpdb->prefix}burst_ab_tests" );
			if ( $lowest == $this->id ) {
				return '';
			} else {
				return $this->id;
			}
		}

		/**
		 * Get a default value
		 *
		 * @param $fieldname
		 *
		 * @return string
		 */

		private function get_default( $fieldname ) {
			if (!$this->set_defaults) return false;

			$default
				= ( isset( burst::$config->fields[ $fieldname ]['default'] ) )
				? burst::$config->fields[ $fieldname ]['default'] : '';

			return $default;
		}


		/**
		 * Save the edited data in the object
		 *
		 * @param bool $is_default
		 *
		 * @return void
		 */

		public function save() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( ! $this->id ) {
				$this->add();
			}

			// $this->banner_version ++;

			//register translations fields
			// $this->register_translation( $this->save_preferences,
			// 	'save_preferences' );
			// $this->register_translation( $this->accept_all,
			// 	'accept_all' );
			// $this->register_translation( $this->view_preferences,
			// 	'view_preferences' );
			// $this->register_translation( $this->category_functional,
			// 	'category_functional' );
			// $this->register_translation( $this->category_all, 'category_all' );
			// $this->register_translation( $this->category_stats,
			// 	'category_stats' );
			// $this->register_translation( $this->category_prefs,
			// 	'category_prefs' );

			// $this->register_translation( $this->accept, 'accept' );
			// $this->register_translation( $this->revoke, 'revoke' );
			// $this->register_translation( $this->dismiss, 'dismiss' );
			// $this->register_translation( $this->message_optin,
			// 	'message_optin' );
			// $this->register_translation( $this->readmore_optin,
			// 	'readmore_optin' );
			// $this->register_translation( $this->accept_informational,
			// 	'accept_informational' );
			// $this->register_translation( $this->message_optout,
			// 	'message_optout' );
			// $this->register_translation( $this->readmore_optout,
			// 	'readmore_optout' );
			// $this->register_translation( $this->readmore_optout_dnsmpi,
			// 	'readmore_optout_dnsmpi' );
			// $this->register_translation( $this->readmore_privacy,
			// 	'readmore_privacy' );
			// $this->register_translation( $this->readmore_impressum,
			// 	'readmore_impressum' );

			/**
			 * If Tag manager fires categories, enable use categories by default
			 */
//            $tm_fires_scripts = burst_get_value('fire_scripts_in_tagmanager') === 'yes' ? true : false;
//            $uses_tagmanager = burst_get_value('compile_statistics') === 'google-tag-manager' ? true : false;
//            if ($uses_tagmanager && $tm_fires_scripts) {
//                $this->use_categories = 'visible';
//            }

			if ( ! is_array( $this->statistics ) ) {
				$this->statistics = array();
			}
			$statistics   = serialize( $this->statistics );
			$update_array = array(
				'archived'            		=> intval( $this->banner_version ),
				'title'                     => sanitize_text_field( $this->title ),
				'variant_id'                => intval( $this->$variant_id ),
				'control_id'                => intval( $this->$control_id ),
				'test_running'              => boolval( $this->$test_running ),
				'date_created'              => sanitize_text_field( $this->$date_created ),
				'date_modified'             => sanitize_text_field( $this->$date_modified ),
				'date_started'              => sanitize_text_field( $this->$date_started ),
				'date_end'                	=> sanitize_text_field( $this->$date_end ),
				'kpi'                		=> sanitize_text_field( $this->$kpi ),
				'statistics'                => $this->$statistics,
			);

			global $wpdb;
			$updated = $wpdb->update( $wpdb->prefix . 'burst_ab_tests',
				$update_array,
				array( 'ID' => $this->id )
			);

		}


		/**
		 * Delete a cookie variation
		 *
		 * @return bool $success
		 * @since 2.0
		 */

		public function delete( $force = false ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return false;
			}

			$error = false;
			global $wpdb;

			//do not delete the last one.
			$count
				= $wpdb->get_var( "select count(*) as count from {$wpdb->prefix}burst_ab_tests" );
			if ( $count == 1 && ! $force ) {
				$error = true;
			}

			if ( ! $error ) {

				$wpdb->delete( $wpdb->prefix . 'burst_ab_tests', array(
					'ID' => $this->id,
				) );

				//clear all statistics regarding this banner
				// $wpdb->delete( $wpdb->prefix . 'burst_statistics', array(
				// 	'ab_test_id' => $this->id,
				// ) );
			}

			return ! $error;
		}

		/**
		 * Archive this cookie banner
		 *
		 * @return void
		 */

		public function archive() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			//don't archive the last one
			if ( count( burst_get_ab_tests() ) === 1 ) {
				return;
			}
      
			$this->archived = true;

			$this->save();
		}

		/**
		 * Restore this ab_test
		 *
		 * @return void
		 */

		public function restore() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$this->archived = false;
			$this->save();
		}

		/**
		 * Get the conversion to marketing for a ab_test
		 *
		 * @return float percentage
		 */

		public function conversion_percentage( $filter_consenttype ) {
			if ( $this->archived ) {
				if ( ! isset( $this->statistics[ $filter_consenttype ] ) ) {
					return 0;
				}
				$total = 0;
				$all   = 0;
				foreach (
					$this->statistics[ $filter_consenttype ] as $status =>
					$count
				) {
					$total += $count;
					if ( $status === 'all' ) {
						$all = $count;
					}
				}

				$total = ( $total == 0 ) ? 1 : $total;
				$score = ROUND( 100 * ( $all / $total ) );
			} else {

				$total = 0;
				$all   = 0;
				foreach ( $statuses as $status ) {
					$count = $this->get_count( $status, $filter_consenttype );

					$total += $count;
					if ( $status === 'all' ) {
						$all = $count;
					}
				}

				$total = ( $total == 0 ) ? 1 : $total;

				$score = ROUND( 100 * ( $all / $total ) );

				return $score;
			}

			return $score;
		}

		/**
		 * Get the count for this status and consenttype.
		 *
		 * @param $status
		 * @param $consenttype
		 *
		 * @return int $count
		 */

		public function get_count( $status, $consenttype = false ) {
			global $wpdb;
			$status          = sanitize_title( $status );
			$consenttype_sql = " AND consenttype='$consenttype'";

			if ( $consenttype === 'all' ) {
				$consenttypes    = burst_get_used_consenttypes();
				$consenttype_sql = " AND (consenttype='"
				                   . implode( "' OR consenttype='",
						$consenttypes ) . "')";
			}

			$sql
				= $wpdb->prepare( "SELECT count(*) from {$wpdb->prefix}burst_statistics WHERE status = %s "
				                  . $consenttype_sql, $status );
			if ( burst::$cookie_admin->ab_testing_enabled() ) {
				$sql = $wpdb->prepare( $sql . " AND ab_test_id=%s",
					$this->id );
			}
			$count = $wpdb->get_var( $sql );

			return $count;
		}

		public function report_conversion_total_count( $statistics ) {
			$total = 0;
			foreach ( $statistics as $status => $count ) {
				$total += $count;
			}

			return $total;
		}

	}

}