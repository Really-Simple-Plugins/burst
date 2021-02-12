<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

/*
 * Install experiment table
 * */

add_action( 'plugins_loaded', 'burst_install_experiments_table', 10 );
function burst_install_experiments_table() {
	if ( get_option( 'burst_abdb_version' ) !== burst_version ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'burst_experiments';
		$sql        = "CREATE TABLE $table_name (
            `ID` int(11) NOT NULL AUTO_INCREMENT,
            `archived` int(11) NOT NULL,
            `title` varchar(255) NOT NULL,
            `variant_id` int(11) NOT NULL,
            `control_id` int(11) NOT NULL,
            `test_running` boolean NOT NULL,
            `date_created` varchar(255) NOT NULL,
            `date_modified` varchar(255) NOT NULL,
            `date_started` varchar(255) NOT NULL,
            `date_end` varchar(255) NOT NULL,
            `goal` text NOT NULL,
            `statistics` text NOT NULL,
              PRIMARY KEY  (ID)
            ) $charset_collate;";
		dbDelta( $sql );
		update_option( 'burst_abdb_version', burst_version );

	}
}

if ( ! class_exists( "BURST_EXPERIMENT" ) ) {
	class BURST_EXPERIMENT {
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
		public $goal = false;
		public $statistics = false;
		public $percentage_included = 100;

		function __construct( $id = false, $set_defaults = true ) {

			$this->id = $id;

			if ( $this->id !== false ) {
				//initialize the experiment settings with this id.
				$this->get();
			}

		}





		/**
		 * Add a new experiment database entry
		 */

		private function add() {
			if ( ! burst_user_can_manage() ) {
				return false;
			}
			$array = array(
				'title' => __( 'New experiment', 'burst' ),
			);

			global $wpdb;

			$wpdb->insert(
				$wpdb->prefix . 'burst_experiments',
				$array
			);
			$this->id = $wpdb->insert_id;
		}



		public function process_form( $post ) {

			if ( ! burst_user_can_manage() ) {
				return false;
			}

			if ( ! isset( $post['burst_nonce'] ) ) {
				return false;
			}

			//check nonce
			if ( ! isset( $post['burst_nonce'] )
			     || ! wp_verify_nonce( $post['burst_nonce'],
					'burst_save_experiment' )
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
		 * Load the experiment data
		 *
		 */

		private function get() {
			global $wpdb;

			if ( ! intval( $this->id ) > 0 ) {
				return;
			}

			$experiments
				= $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}burst_experiments where ID = %s",
				intval( $this->id ) ) );

			if ( isset( $experiments[0] ) ) {
				$experiment         		= $experiments[0];
				$this->archived       		= $experiment->archived;
				$this->title          		= $experiment->title;
				$this->variant_id 			= $experiment->variant_id;
				$this->control_id 			= $experiment->control_id;
				$this->test_running 		= $experiment->test_running;
				$this->date_created 		= $experiment->date_created;
				$this->date_modified 		= $experiment->date_modified;
				$this->date_started 		= $experiment->date_started;
				$this->date_end 			= $experiment->date_end;
				$this->goal 				= $experiment->goal;
				$this->statistics 			= $experiment->statistics;
				$this->percentage_included 	= $experiment->percentage_included;




		

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
			$lowest = $wpdb->get_var( "select min(ID) from {$wpdb->prefix}burst_experiments" );
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
			if ( ! burst_user_can_manage() ) {
				return;
			}

			if ( ! $this->id ) {
				$this->add();
			}

			if ( ! is_array( $this->statistics ) ) {
				$this->statistics = array();
			}
			$statistics   = serialize( $this->statistics );
			$update_array = array(
				'archived'            		=> intval( $this->archived ),
				'title'                     => sanitize_text_field( $this->title ),
				'variant_id'                => intval( $this->variant_id ),
				'control_id'                => intval( $this->control_id ),
				'test_running'              => boolval( $this->test_running ),
				'date_created'              => sanitize_text_field( $this->date_created ),
				'date_modified'             => sanitize_text_field( $this->date_modified ),
				'date_started'              => sanitize_text_field( $this->date_started ),
				'date_end'                	=> sanitize_text_field( $this->date_end ),
				'goal'                		=> sanitize_text_field( $this->goal ),
				'statistics'                => $this->statistics,
				'percentage_included'		=> intval( $this->percentage_included ),
			);
			global $wpdb;
			$updated = $wpdb->update( $wpdb->prefix . 'burst_experiments',
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
			if ( ! burst_user_can_manage() ) {
				return false;
			}

			$error = false;
			global $wpdb;

			//do not delete the last one.
			$count
				= $wpdb->get_var( "select count(*) as count from {$wpdb->prefix}burst_experiments" );
			if ( $count == 1 && ! $force ) {
				$error = true;
			}

			if ( ! $error ) {

				$wpdb->delete( $wpdb->prefix . 'burst_experiments', array(
					'ID' => $this->id,
				) );
			}

			return ! $error;
		}

		/**
		 * Archive this experiment
		 *
		 * @return void
		 */

		public function archive() {
			if ( ! burst_user_can_manage() ) {
				return;
			}
      
			$this->archived = true;

			$this->save();
		}

		/**
		 * Restore this experiment
		 *
		 * @return void
		 */

		public function restore() {
			if ( ! burst_user_can_manage() ) {
				return;
			}

			$this->archived = false;
			$this->save();
		}

		/**
		 * Get the conversion to marketing for a experiment
		 *
		 * @return float percentage
		 *
		 * @todo  Aanpassen of verwijderen
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
		 *
		 * @todo  Aanpassen of verwijderen
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
			if ( burst::$cookie_admin->experimenting_enabled() ) {
				$sql = $wpdb->prepare( $sql . " AND experiment_id=%s",
					$this->id );
			}
			$count = $wpdb->get_var( $sql );

			return $count;
		}
		

	}

}