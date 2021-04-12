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
            `variant_url_parameter` varchar(255) NOT NULL,
            `control_url_parameter` varchar(255) NOT NULL,
            `status` varchar(255) NOT NULL,
            `date_created` varchar(255) NOT NULL,
            `date_modified` varchar(255) NOT NULL,
            `date_started` varchar(255) NOT NULL,
            `date_end` varchar(255) NOT NULL,
            `goal` varchar(255) NOT NULL,
            `goal_id` varchar(255) NOT NULL,
            `minimum_samplesize` int(11) NOT NULL,
            `minimum_samplesize_custom` int(11) NOT NULL,
            `goal_identifier` varchar(255) NOT NULL,
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
		public $variant_url_parameter = false;
		public $control_url_parameter = false;
		public $status = 'draft';
		public $date_created = false;
		public $date_modified = false;
		public $date_started = false;
		public $date_end = false;
		public $goal = false;//visit, click
		public $goal_id = false;
		public $minimum_samplesize = 384;
		public $minimum_samplesize_custom = 0;
		public $goal_identifier = '';
		public $statistics = false;

		/**
		 * An alpha of 0.05, or 5 percent, is standard, but if you're running a particularly sensitive experiment,
		 * such as testing a medicine or building an airplane, 0.01 may be more appropriate.
		 * For our a/b experiment, a 0.05 alpha is fine.
		 *
		 * @var float
		 */
		private $alpha_level = 0.05;

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
				'date_created' => time(),
			);

			global $wpdb;
			$wpdb->insert(
				$wpdb->prefix . 'burst_experiments',
				$array
			);
			$this->id = $wpdb->insert_id;
		}

		/**
		 * Sanitize the goal type
		 * @param string $str
		 *
		 * @return string
		 */

		private function sanitize_goal( $str ){
			$goals = array(
				'visit',
				'click'
			);

			if ( in_array( $str, $goals)) {
				return $str;
			} else {
				return 'visit';
			}
		}
		/**
		 * Sanitize the test type
		 * @param string $str
		 *
		 * @return string
		 */

		private function sanitize_test_type( $str ){
			$types = array(
				'control',
				'variant'
			);

			if ( in_array( $str, $types)) {
				return $str;
			} else {
				return 'control';
			}
		}

		public function process_form( $post ) {

			if ( ! burst_user_can_manage() ) {
				return false;
			}

			if ( ! isset( $post['burst_nonce'] ) ) {
				return false;
			}

			//check nonce
			if ( ! isset( $_POST['burst_nonce'] )
			     || ! wp_verify_nonce( $_POST['burst_nonce'],
					'burst_save' )
			) {
				return false;
			}

			//sanitizing in save function
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
			$experiment = $wpdb->get_row( $wpdb->prepare( "select * from {$wpdb->prefix}burst_experiments where ID = %s", intval( $this->id ) ) );
			if ( $experiment ) {
				$this->title          				= $experiment->title;
				$this->variant_id 					= $experiment->variant_id;
				$this->control_id 					= $experiment->control_id;
				$this->variant_url_parameter 		= $experiment->variant_url_parameter;
				$this->control_url_parameter 		= $experiment->control_url_parameter;
				$this->status 		        		= $experiment->status;
				$this->date_created 				= $experiment->date_created;
				$this->date_modified 				= $experiment->date_modified;
				$this->date_started 				= $experiment->date_started;
				$this->date_end 					= $experiment->date_end;
				$this->goal 						= $experiment->goal;
				$this->goal_id 		        		= $experiment->goal_id;
				$this->minimum_samplesize 		    = $experiment->minimum_samplesize;
				$this->minimum_samplesize_custom	= $experiment->minimum_samplesize_custom;
				$this->goal_identifier 				= $experiment->goal_identifier;
				$this->statistics 					= $experiment->statistics;
			}
		}

		/**
		 * Start the experiment
		 */

		public function start(){
			$this->status = 'active';
			$this->date_started = time();
			$this->save();
		}

		/**
		 * Start the experiment
		 */

		public function stop(){
			$this->status = 'completed';
			$this->date_end = time();
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
			$variant_url_parameter = burst_random_str('12');
			$control_url_parameter = burst_random_str('12');
			error_log('control url parameter');
			error_log($control_url_parameter);

			$update_array = array(
				'title'                     => sanitize_text_field( $this->title ),
				'variant_id'                => intval( $this->variant_id ),
				'control_id'                => intval( $this->control_id ),
				'variant_url_parameter'     => sanitize_text_field( $variant_url_parameter ),
				'control_url_parameter'     => sanitize_text_field( $control_url_parameter ),
				'status'                    => burst_sanitize_experiment_status( $this->status ),
				'date_created'              => sanitize_text_field( $this->date_created ),
				'date_modified'             => time(),
				'date_started'              => sanitize_text_field( $this->date_started ),
				'date_end'                  => sanitize_text_field( $this->date_end ),
				'goal'                      => $this->sanitize_goal( $this->goal ),
				'goal_identifier'           => sanitize_text_field( $this->goal_identifier ),
				'goal_id'                   => intval( $this->goal_id ),
				'minimum_samplesize'        => $this->minimum_samplesize == - 1 ? intval( $this->minimum_samplesize_custom ) : intval( $this->minimum_samplesize ),
				'minimum_samplesize_custom' => intval( $this->minimum_samplesize_custom ),
			);
			global $wpdb;
			$updated = $wpdb->update( $wpdb->prefix . 'burst_experiments',
				$update_array,
				array( 'ID' => $this->id )
			);

			update_post_meta( $this->control_id,'burst_experiment_id', $this->id );
			update_post_meta( $this->variant_id,'burst_experiment_id', $this->id );
			if ( $this->goal === 'visit' ) {
				update_post_meta( $this->goal_id,'burst_experiment_id', $this->id );
			}
		}

		/**
		 * Activate winning variant
		 */

		public function activate_winner(){
			$success_rate_control = $this->get_average('control');
			$success_rate_variant = $this->get_average('variant');
//			if ( $success_rate_variant > $success_rate_control ) {
//				//get post content of control, and save it in temp
//				$args = array(
//					'post_title' => __('Temporary post', 'burst'),
//					'post_status' => 'draft',
//					'post_type' => 'post',
//				);
//				$temp_id = wp_insert_post($args);
//				BURST::$admin->copy_post( $this->control_id, $temp_id );
//
//				//get post content of variant, and save it in control
//				//as control is already published, this should do the trick.
//				BURST::$admin->copy_post( $this->variant_id, $this->control_id );
//
//				//get post content of temp (original control), and save it in variant
//				BURST::$admin->copy_post( $temp_id, $this->variant_id );
//
//				//clean up temp post
//				wp_delete_post( $temp_id );
//			}
			$this->stop();
		}

		/**
		 * Load the statistic data by experiment id
		 * all hits from experiment
		 *
		 * @param array $args
		 *
		 * @return int
		 */

		public function count_hits( $args ) {
			$default_args = array(
				'test_version' => 'control',
			);
			$sql = '';
			$args = wp_parse_args( $args, $default_args );
			global $wpdb;
			$test_version = burst_sanitize_test_version( $args['test_version']);
			if ( isset($args['converted']) ) {
				$converted = $args['converted'] ? 'true' : 'false';
				$sql = ' AND conversion = '.$converted;
			}
			return $wpdb->get_var( $wpdb->prepare( "select count(*) from {$wpdb->prefix}burst_statistics where experiment_id = %s and test_version = %s ".$sql, intval( $this->id ), $test_version ) );
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
			$this->date_modified = time();
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
			$this->date_modified = time();
			$this->save();
		}

		/**
		 * Calculate the standard deviation of our data
		 * https://blog.prepscholar.com/statistical-significance-definition
		 * @param string $type
		 * @return float
		 */

		private function get_standard_deviation( $type ) {
			global $wpdb;
			$type = $this->sanitize_test_type($type);
			return $wpdb->get_var( $wpdb->prepare(
				"select SQRT(sum(POWER(conversion - average, 2))/(avg(total)-1)) as m from {$wpdb->prefix}burst_statistics 
    					CROSS JOIN (select * from (select data.total as total, round(data.converted/data.total, 2) as average from 
    					(SELECT COUNT(*) AS total, (SELECT COUNT(*) FROM {$wpdb->prefix}burst_statistics 
    					WHERE experiment_id=%s and test_version = %s and conversion=1) as converted FROM 
    					  {$wpdb->prefix}burst_statistics WHERE experiment_id=%s and test_version = %s ) as data) as perc) as complete",
				intval( $this->id ),
				$type,
				intval( $this->id ),
				$type
			) );
		}

		/**
		 * @param string $type
		 *
		 * @return float
		 */
		private function get_average( $type ) {
			global $wpdb;
			$type = $this->sanitize_test_type($type);
			return $wpdb->get_var( $wpdb->prepare(
				"select round(data.converted/data.total, 2) as average from 
    					(SELECT COUNT(*) AS total, (SELECT COUNT(*) FROM {$wpdb->prefix}burst_statistics 
    					WHERE experiment_id=%s and test_version=%s and conversion=1) as converted FROM 
    					  {$wpdb->prefix}burst_statistics WHERE experiment_id=%s and test_version=%s  ) as data",
				intval( $this->id ),
				$type,
				intval( $this->id ),
				$type
			) );
		}


		/**
		 * @param string $type
		 *
		 * @return int
		 */
		private function get_sample_size( $type = false ) {
			global $wpdb;
			$type = $this->sanitize_test_type($type);

			$sql = $type ? " and test_version = '".$type."'" : '';
			return $wpdb->get_var( $wpdb->prepare( "select count(*) from {$wpdb->prefix}burst_statistics WHERE experiment_id=%s".$sql, intval( $this->id ) ) );
		}

		/**
		 * Determine if experiment is significant, based on alpha and significance value.
		 *
		 * @return bool
		 */
		public function is_statistical_significant(){

			$significance = $this->get_significance();
			if ( $significance && $this->alpha_level > $significance ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Get the significance value
		 */

		public function get_significance(){
			//https://blog.prepscholar.com/statistical-significance-definition

			// set null hypothesis, H0
			// Control and variant both 50% vs 50%

			// alternative hypothesis
			// Control wins

			$confidence_level = 100 * (1-$this->alpha_level); //95%

			//one tailed test: We only Change page A into B, if B is better. Not if it's worse.
			//https://blog.analytics-toolkit.com/2017/one-tailed-two-tailed-tests-significance-ab-testing/


			//get standard deviation $s
			$s_control = $this->get_standard_deviation('control');
			$s_control_sample_size = $this->get_sample_size('control');

			$s_variant = $this->get_standard_deviation('variant');
			$s_variant_sample_size = $this->get_sample_size('variant');

			//if the sample size is 0, there's no data we can use
			if ( $s_control_sample_size == 0 || $s_variant_sample_size == 0 ) {
				return false;
			}

			$standard_deviation = sqrt(($s_control/$s_control_sample_size) + ($s_variant/$s_variant_sample_size));

			$m1 = $this->get_average( 'control');
			$m2 = $this->get_average( 'variant');
			$t_score = abs($m1-$m2)/$standard_deviation;

			$degrees_of_freedom = abs($s_control_sample_size + $s_variant_sample_size -2 );

			return $this->get_significance_from_t_table( $degrees_of_freedom, $t_score );
		}

		/**
		 * Calculate required sample size
		 * https://www.statisticshowto.com/probability-and-statistics/find-sample-size/
		 * @return float
		 */
		public function get_required_sample_size(){
			//sample size $n0
			$Z = 1.96; //based on assumption in article, where confidence of 95% suggests a Z of 1.96.
			$p = 0.5; //is the (estimated) proportion of the population which prefers the control variant. We assume 50%,
			$q = 1-$p;
			$e = 0.05; //margin of error
			return ( $Z*$Z * $p * $q ) / ( $e * $e );
		}

		/**
		 * Check if minimum sample size has been reached
		 *
		 * @return bool
		 */
		public function has_reached_minimum_sample_size(){
			return $this->get_sample_size() > $this->minimum_samplesize;
		}

		/**
		 * @return float
		 *
		 *
	     * https://stats.stackexchange.com/questions/364547/how-to-analytically-solve-the-probability-of-improvement-acquisition-function-in
		 */
		public function probability_of_improvement(){
			$standard_deviation = $this->get_standard_deviation('control');

			if ( $standard_deviation ==0 ){
				return 0;
			}

			$max_found_value = 1;
			$args = array(
				'test_version' => 'control',
				'converted' => true,
			);
			$converted = $this->count_hits($args);
			if ( $converted == 0 ) {
				$max_found_value = 0;
			}

			$mu = $this->get_average('control');
			$CDF = $this->cumulative_density_function($mu);
			return round(100 * $CDF - $max_found_value/$standard_deviation, 1);
		}

		/**
		 * Get cumulative density function value
		 * @param $x
		 *
		 * https://stackoverflow.com/questions/4304765/how-to-generate-a-cumulative-normal-distribution-in-php
		 * https://github.com/shadiakiki1986/php-blackscholes/blob/master/src/BlackScholesStatic.php
		 *
		 * @return float
		 */

		private function cumulative_density_function($x)
		{
			$Pi = 3.141592653589793238;
			$a1 = 0.319381530;
			$a2 = -0.356563782;
			$a3 = 1.781477937;
			$a4 = -1.821255978;
			$a5 = 1.330274429;
			$L = abs($x);
			$k = 1 / ( 1 + 0.2316419 * $L);
			$p = 1 - 1 /  pow(2 * $Pi, 0.5) * exp( -pow($L, 2) / 2 ) * ($a1 * $k + $a2 * pow($k, 2)
			                                                            + $a3 * pow($k, 3) + $a4 * pow($k, 4) + $a5 * pow($k, 5) );
			if ($x >= 0) {
				return $p;
			} else {
				return 1-$p;
			}
		}

		/**
		 * Calculate the chance that the control wins
		 * @return float
		 */
		public function probability_of_control_winning(){
			$m1 = $this->get_average( 'control');
			$m2 = $this->get_average( 'variant');
			return round(100 * $m1 / ($m1 + $m2), 1);
		}

		/**
		 * Get margin of error based on sample size, in %
		 * https://www.statisticshowto.com/probability-and-statistics/find-sample-size/
		 *
		 * @return float
		 */
		public function get_margin_of_error(){
			$n = $this->get_sample_size();
			//without any data, it's 100%
			if ($n==0) return 100;

			//sample size $n0
			$Z = 1.96; //based on assumption in article, where confidence of 95% suggests a Z of 1.96.
			$p = 0.5; //is the (estimated) proportion of the population which prefers the control variant. We assume 50%,
			$q = 1-$p;
			return round(100 * sqrt( $Z*$Z * $p * $q )/$n, 1);
		}

		/**
		 * http://socr.ucla.edu/Applets.dir/T-table.html
		 *
		 * Look up $df, and closest $t value, and return $p.
		 */

		private function get_significance_from_t_table($df, $t){

			$p = array(
				0 => 0.1,
				1 => 0.05,
				2 => 0.025,
				3 => 0.01,
				4 => 0.005,
				5 => 0.001,
				6 => 0.0005,
			);
			/**
			 * index  = df, degrees of freedom
			 *
			 */
			$table = array(
				1=> array(3.078,6.314,12.706,31.821,63.656,	318.289,636.578),
				2=> array(1.886,2.920,4.303,6.965,9.925,22.328,31.600),
				3=> array(1.638,2.353,3.182,4.541,5.841,10.214,12.924),
				4=> array(1.533,2.132,2.776,3.747,4.604,7.173,8.610),
				5=> array(1.476,2.015,2.571,3.365,4.032,5.894,6.869),
				6=> array(1.440,1.943,2.447,3.143,3.707,5.208,5.959),
				7=> array(1.415,1.895,2.365,2.998,3.499,4.785,5.408),
				8=> array(1.397,1.860,2.306,2.896,3.355,4.501,5.041),
				9=> array(1.383,1.833,2.262,2.821,3.250,4.297,4.781),
				10=> array(1.372,1.812,2.228,2.764,3.169,4.144,4.587),
				11=> array(1.363,1.796,2.201,2.718,3.106,4.025,4.437),
				12=> array(1.356,1.782,2.179,2.681,3.055,3.930,4.318),
				13=> array(1.350,1.771,2.160,2.650,3.012,3.852,4.221),
				14=> array(	1.345,1.761,2.145,2.624,2.977,3.787,4.140),
				15=> array(	1.341,1.753,2.131,2.602,2.947,3.733,4.073),
				16=> array(	1.337,1.746,2.120,2.583,2.921,3.686,4.015),
				17=> array(	1.333,1.740,2.110,2.567,2.898,3.646,3.965),
				18=> array(	1.330,1.734,2.101,2.552,2.878,3.610,3.922),
				19=> array(	1.328,1.729,2.093,2.539,2.861,3.579,3.883),
				20=> array(	1.325,1.725,2.086,2.528,2.845,3.552,3.850),
				21=> array(	1.323,1.721,2.080,2.518,2.831,3.527,3.819),
				22=> array(	1.321,1.717,2.074,2.508,2.819,3.505,3.792),
				23=> array(	1.319,1.714,2.069,2.500,2.807,3.485,3.768),
				24=> array(	1.318,1.711,2.064,2.492,2.797,3.467,3.745),
				25=> array(	1.316,1.708,2.060,2.485,2.787,3.450,3.725),
				26=> array(	1.315,1.706,2.056,2.479,2.779,3.435,3.707),
				27=> array(	1.314,1.703,2.052,2.473,2.771,3.421,3.689),
				28=> array(	1.313,1.701,2.048,2.467,2.763,3.408,3.674),
				29=> array(	1.311,1.699,2.045,2.462,2.756,3.396,3.660),
				30=> array(	1.310,1.697,2.042,2.457,2.750,3.385,3.646),
				60=> array(	1.296,1.671,2.000,2.390,2.660,3.232,3.460),
				120=> array(1.289,1.658,1.980,2.358,2.617,3.160,3.373),
				0 => array(	1.282,1.645,1.960,2.326,2.576,3.091,3.291),
			);

			if ( $df>30 ){
				$df = 60;
			} else if ( $df>60) {
				$df = 120;
			} else if ( $df>120 ) {
				$df=0;
			}
			$row = $table[$df];

			$index = $this->get_closest_value($t, $row);
			return $p[$index];
		}

		/**
		 * @param $search
		 * @param $arr
		 *
		 * @return mixed|null
		 */
		private function get_closest_value($search, $arr) {
			//first, check if the value is larger than the largest value
			$max = max($arr);
			if ($search>$max) {
				return max(array_keys($arr));
			}

			$closest = null;
			foreach ($arr as $item) {
				if ($closest === null || abs($search - $closest) > abs($item - $search)) {
					$closest = $item;
				}
			}
			return array_search($closest, $arr);
		}

	}

}