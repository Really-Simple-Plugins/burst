<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

/**
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
            `experiment_id` int(11) NOT NULL,
            `test_version` varchar(255) NOT NULL,
            `page_url` varchar(255) NOT NULL,
            `time` varchar(255) NOT NULL,
            `uid` varchar(255) NOT NULL,
            `test_version` varchar(255) NOT NULL,
            `conversion` int(11) NOT NULL,
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
		public $experiment_id;
		public $conversion;
		public $data;

		function __construct( $experiment_id = false ) {
			if ($experiment_id) {
				$this->experiment_id = $experiment_id;
				$this->get();
			}
		}

		/**
		 * Add a new statistic database entry
		 */

		public function track() {
			global $wpdb;

			$update_array = array(
				'page_url'            		=> sanitize_text_field( $this->page_url ),
				'page_id'                   => intval( $this->page_id ),
				'time'               		=> time(),
				'uid'               		=> sanitize_title($this->uid),
				'test_version'				=> $this->sanitize_test_version($this->test_version),
				'experiment_id'				=> intval($this->experiment_id),
				'conversion'				=> intval($this->conversion),
			);

			//check if the current users' uid/experiment id combination is already in the database.
			$this->id = $wpdb->get_var( $wpdb->prepare( "select ID from {$wpdb->prefix}burst_statistics where experiment_id = %s and uid = %s", intval( $this->experiment_id ), sanitize_title($this->uid) ) );
			if ($this->id) {
				$wpdb->update(
					$wpdb->prefix . 'burst_statistics',
					$update_array,
					array('ID' => $this->id)
				);
				$this->id = $wpdb->insert_id;
			} else {
				$wpdb->insert(
					$wpdb->prefix . 'burst_statistics',
					$update_array
				);
				$this->id = $wpdb->insert_id;
			}
		}

		/**
		 * Load the statistic data by experiment id
		 *
		 */

		private function get() {
			global $wpdb;
			$statistics = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}burst_statistics where experiment_id = %s", esc_attr( $this->experiment_id ) ) );
			if ( !empty($statistics) ) {
				$this->data = $statistics;
				return true;
			}
			return false;
		}

		/**
		 * Sanitize the test version
		 * @param string $str
		 *
		 * @return string
		 */

		private function sanitize_test_version( $str){
			$test_versions = array(
				'variant',
				'control'
			);

			if ( in_array( $str, $test_versions)) {
				return $str;
			} else {
				return 'control';
			}
		}
	}

}