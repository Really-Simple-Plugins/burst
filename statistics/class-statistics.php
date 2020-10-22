<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

/*
 * Install statistic table
 * */

add_action( 'plugins_loaded', 'burst_install_statistics_table', 10 );
function burst_install_statistics_table() {
	if ( get_option( 'burst_stats_db_version' ) !== burst_version ) {
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$table_name = $wpdb->prefix . 'burst_statistics';
		$sql        = "CREATE TABLE $table_name (
			`ID` int(11) NOT NULL AUTO_INCREMENT ,
            `page_id` int(11) NOT NULL,
            `page_url` varchar(255) NOT NULL,
            `time` varchar(255) NOT NULL,
            `uid` varchar(255) NOT NULL,
            `test_version` varchar(255) NOT NULL,
              PRIMARY KEY  (ID)
            ) $charset_collate;";
		dbDelta( $sql );
		update_option( 'burst_stats_db_version', burst_version );

	}
}

if ( ! class_exists( "BURST_STATISTICS" ) ) {
	class BURST_STATISTICS{
		public $id = false;
		public $page_url = false;
		public $page_id = false;
		public $time; //timestamp(seconds from 01-01-1970);
		public $uid;
		public $test_version;
		//public $clicked; //array('timestamp(seconds from 01-01-1970)' => clicked on url );
		//public $referer; //array('timestamp(seconds from 01-01-1970)' => previous URL );

		function __construct( $page_url = false, $uid = false ) {

			$this->uid = $uid;
			$this->page_url = $page_url;

		}





		/**
		 * Add a new statistic database entry
		 */

		private function add() {
			// if ( ! current_user_can( 'manage_options' ) ) {
			// 	return false;
			// }

			$array = array(
				'page_url' => $this->page_url,
				'uid' => $this->uid,
				'time' => time(),
			);

			global $wpdb;

			$wpdb->insert(
				$wpdb->prefix . 'burst_statistics',
				$array
			);
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Load the statistic data
		 *
		 */

		private function get() {
			global $wpdb;

			$statistics
				= $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}burst_statistics where id = %s",
				esc_attr( $this->id ) ) );

			if ( isset( $statistics[0] ) ) {
				$statistic          = $statistics[0];
				$this->page_url     = $statistic->page_url;
				$this->page_id      = $statistic->page_id;
				$this->time 		= $statistic->time;
				$this->uid 			= $statistic->uid;
				return true;
			}
			return false;

		}

		/**
		 * Save the edited data in the object
		 *
		 * @param bool $is_default
		 *
		 * @return void
		 */

		public function save() {
			
			$this->add();

			$update_array = array(
				'page_url'            		=> esc_attr( $this->page_url ),
				'page_id'                   => intval( $this->page_id ),
				'time'               		=> time(),
				'uid'               		=> $this->uid,
			);
			error_log('update array');
			error_log(print_r($update_array, true));
			global $wpdb;
			$updated = $wpdb->update( $wpdb->prefix . 'burst_statistics',
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
				= $wpdb->get_var( "select count(*) as count from {$wpdb->prefix}burst_statistics" );
			if ( $count == 1 && ! $force ) {
				$error = true;
			}

			if ( ! $error ) {

				$wpdb->delete( $wpdb->prefix . 'burst_statistics', array(
					'page_id' => $this->page_id,
				) );

				//clear all statistics regarding this banner
				// $wpdb->delete( $wpdb->prefix . 'burst_statistics', array(
				// 	'statistic_id' => $this->id,
				// ) );
			}

			return ! $error;
		}

	}

}