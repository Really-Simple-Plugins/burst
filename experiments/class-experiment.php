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
            `title` varchar(255) NOT NULL,
            `variant_id` int(11) NOT NULL,
            `control_id` int(11) NOT NULL,
            `status` varchar(255) NOT NULL,
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
		public $title;
		public $variant_id = false;
		public $control_id = false;
		public $status = 'draft';
		public $date_created = false;
		public $date_modified = false;
		public $date_started = false;
		public $date_end = false;
		public $goal = false;
		public $statistics = false;

		function __construct( $id = false, $post_id = false ) {

			//if a post id is passed, use the post id to find the linked experiment
			if ( !$id && is_numeric($post_id) ) {
				$this->id = burst_get_experiment_id_for_post($post_id);
			} else {
				$this->id = $id;
			}

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

			error_log("#1");
			$experiment = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}burst_experiments where ID = %s", intval( $this->id ) ) );
			error_log("#2");

			if ( $experiment ) {
				$this->title          		= $experiment->title;
				$this->variant_id 			= $experiment->variant_id;
				$this->control_id 			= $experiment->control_id;
				$this->status 		        = $experiment->status;
				$this->date_created 		= $experiment->date_created;
				$this->date_modified 		= $experiment->date_modified;
				$this->date_started 		= $experiment->date_started;
				$this->date_end 			= $experiment->date_end;
				$this->goal 				= $experiment->goal;
				$this->statistics 			= $experiment->statistics;

			}

		}

		/**
		 * Start the experiment
		 */

		public function start(){
			$this->status = 'active';
			$this->date_modified = time();
			$this->save();
		}

		/**
		 * Start the experiment
		 */

		public function stop(){
			$this->status = 'completed';
			$this->date_modified = time();
			$this->save();
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

			$update_array = array(
				'title'                     => sanitize_text_field( $this->title ),
				'variant_id'                => intval( $this->variant_id ),
				'control_id'                => intval( $this->control_id ),
				'status'                    => burst_sanitize_experiment_status( $this->status ),
				'date_created'              => sanitize_text_field( $this->date_created ),
				'date_modified'             => sanitize_text_field( $this->date_modified ),
				'date_started'              => sanitize_text_field( $this->date_started ),
				'date_end'                	=> sanitize_text_field( $this->date_end ),
				'goal'                		=> sanitize_text_field( $this->goal ),
				'statistics'                => $this->statistics,
			);
			global $wpdb;
			$updated = $wpdb->update( $wpdb->prefix . 'burst_experiments',
				$update_array,
				array( 'ID' => $this->id )
			);

		}

		/**
		 * Delete an experiment
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
      
			$this->status = 'archived';

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

			$this->status = 'draft';
			$this->save();
		}

	}

}