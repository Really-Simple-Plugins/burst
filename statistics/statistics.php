<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

add_action( 'wp_ajax_burst_get_experiment_statistics', 'burst_get_experiment_statistics' );
/**
 * Function for getting statistics for display with Chart JS
 * @return json                     Returns a JSON that is compatible with Chart JS
 *
 * @todo  Real data should be displayed here
 */
function burst_get_experiment_statistics(){
	$error = false;
//	if ( ! burst_user_can_manage() ) {
//		$error = true;
//	}
//
//	if ( !isset($_GET['experiment_id'])) {
//		$error = true;
//	}
//
//	if ( !$error ) {
//		$experiment_id = intval( $_GET['experiment_id'] );
//	}

	if ( !$error ) {
//		$consenttype = sanitize_title($_GET['consenttype']);
//		$cat = sanitize_title($_GET['category']);
//		$range = apply_filters('cmplz_ab_testing_duration', cmplz_get_value('a_b_testing_duration')) * DAY_IN_SECONDS;
//
//		//for each day, counting back from "now" to the first day, get the date.
//		$now = time();
//		$start_time = $now - $range;
//		$nr_of_periods = $this->get_nr_of_periods('DAY', $start_time );
//		$data = array();
//		for ($i = $nr_of_periods; $i >= 0; $i--) {
//			$unix_day = strtotime("-$i days");
//			$date = date( get_option( 'date_format' ), $unix_day);
//			$data['labels'][] = $date;
//		}

		//generate a dataset for each category
//		$cookiebanners = cmplz_get_cookiebanners();
//		$i=0;
//		$ab_testing_enabled = cmplz_ab_testing_enabled();
//		foreach ($cookiebanners as $cookiebanner ) {
//			//when not ab testing, show only default banner.
//			if ( !$ab_testing_enabled && !$cookiebanner->default ) continue;
//
//			$cookiebanner = new CMPLZ_COOKIEBANNER( $cookiebanner->ID);
//			$borderDash = array(0,0);
//			$title = empty($cookiebanner->title) ? 'banner_'.$cookiebanner->position.'_'.$i : $cookiebanner->title;
//
//			if (!$cookiebanner->default) {
//				$borderDash = array(10,10);
//			} else {
//				$title .= " (".__("default", "burst").")";
//			}
//
//			//get hits grouped per timeslot. default day
//			$hits = $this->get_grouped_consent_array($cookiebanner->id, $cat, $consenttype, $start_time );
//			$data['datasets'][] = array(
//				'data' => $hits,
//				'backgroundColor' => $this->get_graph_color($i, 'background'),
//				'borderColor' => $this->get_graph_color($i),
//				'label' => $title,
//				'fill' => 'false',
//				'borderDash' => $borderDash,
//			);
//			$i++;
//		}

		$data = array(
			'labels' => array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'),
			'datasets' => array( array(
				'data' =>[8, 13, 8, 9, 6, 0, false],
				'backgroundColor' => 'rgba(231, 126, 35, 0.2)',
				'borderColor' => 'rgba(231, 126, 35, 1)',
				'label' => 'Original'
			),
				array(
					'data' => array(8, 9, 12, 20, 6, 2.5 ,3),
					'backgroundColor' => 'rgba(51, 152, 219, 0.2)',
					'borderColor' => 'rgba(51, 152, 219, 1)',
					'label' => 'Variation'
				))
		);

	}

	if (isset($data['datasets'])) {
		//get highest hit count for max value
		$max = max(array_map('max',array_column( $data['datasets'], 'data' )));
		$data['max'] = $max > 5 ? $max : 5;
	} else {
		$data['datasets'][] = array(
			'data' => array(0),
			'backgroundColor' => burst_get_graph_color(0, 'background'),
			'borderColor' => burst_get_graph_color(0),
			'label' => __("No data for this selection", "burst"),
			'fill' => 'false',
		);
		$data['max'] = 5;
	}

	$return  = array(
		'success' => !$error,
		'data'    => $data,
		'title'    => __('Experiment', "burst"),
	);
	echo json_encode( $return );
	die;
}



/**
 * Get color for a graph
 * @param int     $index
 * @param string $type
 *
 * @return string
 */

function burst_get_graph_color( $index , $type = 'default' ) {
	$o = $type = 'background' ? '1' : '1';
	switch ($index) {
		case 0:
			return "rgba(255, 99, 132, $o)";
		case 1:
			return "rgba(255, 159, 64, $o)";
		case 2:
			return "rgba(255, 205, 86, $o)";
		case 3:
			return "rgba(75, 192, 192, $o)";
		case 4:
			return "rgba(54, 162, 235, $o)";
		case 5:
			return "rgba(153, 102, 255, $o)";
		case 6:
			return "rgba(201, 203, 207, $o)";
		default:
			return "rgba(238, 126, 35, $o)";

	}
}

/**
 * Get the latest visit for a UID for a specific page. 
 * Specify a data_variable if you just want the result for a specific parameter
 * 
 * @param  integer $burst_uid     The Burst UID which is saved in a cookie 
 *                                (and in the user meta if the user is logged in)
 * @param  string  $page_url      The page URL you want the latest visit from
 * @param  string  $data_variable Specify which data you want, if left empty you'll 
 *                                get an object with everything
 * @return object                 Returns the latest visit data        
 */
function burst_get_latest_visit_data($burst_uid = false, $page_url = false, $data_variable = false){
	error_log('burst_get_latest_visit');
	if (!$burst_uid && !$page_url) {
		return false; 
	}
	$sql = "";
	if ($page_url) {
		$sql = " AND page_url ='" . esc_attr($page_url) . "' ";

	}

	global $wpdb;
	if ($burst_uid) {
		$statistics
		= $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}burst_statistics where uid = %s". $sql ." ORDER BY time DESC LIMIT 1 ",
		esc_attr( $burst_uid) ) );
	}
	error_log('stats');
	error_log(print_r($statistics, true));
	if (empty($statistics)){
		return false;
	} else {

		if ($data_variable) {
			return $statistics[0]->$data_variable;
		} else {
			return $statistics;	
		}
		
	}	
	
}
/**
 * Function to get the current URL used in the load_experiment_content function
 * @return url The current URL
 */
function burst_get_current_url() {
        return parse_url(get_permalink(), PHP_URL_PATH);
    }
