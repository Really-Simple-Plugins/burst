<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! function_exists( 'burst_uses_google_analytics' ) ) {

	/**
	 * Check if site uses google analytics
	 * @return bool
	 */

	function burst_uses_google_analytics() {
		return BURST::$cookie_admin->uses_google_analytics();
	}
}

if ( ! function_exists( 'burst_user_can_manage' ) ) {
	/**
	 * Check if user has Burst permissions 
	 * @return boolean true or false
	 */
	function burst_user_can_manage() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'burst_get_value' ) ) {

	/**
	 * Get value for an a burst option
	 * For usage very early in the execution order, use the $page option. This bypasses the class usage.
	 *
	 * @param      $fieldname
	 * @param bool $post_id
	 * @param bool $page
	 * @param bool $use_default
	 *
	 * @return array|bool|mixed|string
	 */

	function burst_get_value(
		$fieldname, $post_id = false, $page = false, $use_default = true
	) {
		if ( ! is_numeric( $post_id ) ) {
			$post_id = false;
		}

		if ( ! $page && ! isset( BURST::$config->fields[ $fieldname ] ) ) {
			return false;
		}

		//if  a post id is passed we retrieve the data from the post
		if ( ! $page ) {
			$page = BURST::$config->fields[ $fieldname ]['source'];
		}
		if ( $post_id && ( $page !== 'wizard' ) ) {
			$value = get_post_meta( $post_id, $fieldname, true );
		} else {
			$fields = get_option( 'burst_options_' . $page );

			$default = ( $use_default && $page
			             && isset( BURST::$config->fields[ $fieldname ]['default'] ) )
				? BURST::$config->fields[ $fieldname ]['default'] : '';
			$value   = isset( $fields[ $fieldname ] ) ? $fields[ $fieldname ]
				: $default;
		}

		/*
         * Translate output
         *
         * */

		$type = isset( BURST::$config->fields[ $fieldname ]['type'] )
			? BURST::$config->fields[ $fieldname ]['type'] : false;
		if ( $type === 'cookies' || $type === 'thirdparties'
		     || $type === 'processors'
		) {
			if ( is_array( $value ) ) {

				//this is for example a cookie array, like ($item = cookie("name"=>"_ga")

				foreach ( $value as $item_key => $item ) {
					//contains the values of an item
					foreach ( $item as $key => $key_value ) {
						if ( function_exists( 'pll__' ) ) {
							$value[ $item_key ][ $key ] = pll__( $item_key . '_'
							                                     . $fieldname
							                                     . "_" . $key );
						}
						if ( function_exists( 'icl_translate' ) ) {
							$value[ $item_key ][ $key ]
								= icl_translate( 'burst',
								$item_key . '_' . $fieldname . "_" . $key,
								$key_value );
						}

						$value[ $item_key ][ $key ]
							= apply_filters( 'wpml_translate_single_string',
							$key_value, 'burst',
							$item_key . '_' . $fieldname . "_" . $key );
					}
				}
			}
		} else {
			if ( isset( BURST::$config->fields[ $fieldname ]['translatable'] )
			     && BURST::$config->fields[ $fieldname ]['translatable']
			) {
				if ( function_exists( 'pll__' ) ) {
					$value = pll__( $value );
				}
				if ( function_exists( 'icl_translate' ) ) {
					$value = icl_translate( 'burst', $fieldname, $value );
				}

				$value = apply_filters( 'wpml_translate_single_string', $value,
					'burst', $fieldname );
			}
		}

		return $value;
	}
}

if ( ! function_exists( 'burst_get_experiments' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param array $args
	 *
	 * @return stdClass Object
	 */

	function burst_get_experiments( $args = array() ) {
		$args = wp_parse_args( $args, array( 'status' => 'active' ) );
		$sql  = '';
		global $wpdb;
		if ( $args['status'] === 'archived' ) {
			$sql = 'AND cdb.archived = true';
		}
		if ( $args['status'] === 'active' ) {
			$sql = 'AND cdb.archived = false';
		}

		$experiments
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_experiments as cdb where 1=1 $sql" );

		return $experiments;
	}
}

if ( ! function_exists( 'burst_get_experiments_by' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param string     $field The field to retrieve the user with. id | ID | title | variant_id | control_id | date_created
	 * @param int|string $value A value for $field. ID | title | date
	 *	 *
	 * @return stdClass Object
	 */

	function burst_get_experiments_by( $field, $value ) {
		global $wpdb;

		$experiments
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_experiments as cdb where {$field} = {$value}" );

		return $experiments;
	}
}

if (!function_exists('burst_read_more')) {
	/**
	 * Create a generic read more text with link for help texts.
	 *
	 * @param string $url
	 * @param bool   $add_space
	 *
	 * @return string
	 */
	function burst_read_more( $url, $add_space = true ) {
		$html
			= sprintf( __( "For more information on this subject, please read this %sarticle%s",
			'burst' ), '<a target="_blank" href="' . $url . '">',
			'</a>' );
		if ( $add_space ) {
			$html = '&nbsp;' . $html;
		}

		return $html;
	}
}

if ( ! function_exists( 'burst_get_template' ) ) {
	/**
	 * Get a template based on filename, overridable in theme dir
	 * @param $filename
	 *
	 * @return string
	 */

	function burst_get_template( $filename , $args = array() ) {

		$file       = trailingslashit( burst_path ) . 'templates/' . $filename;
		$theme_file = trailingslashit( get_stylesheet_directory() )
		              . trailingslashit( basename( burst_path ) )
		              . 'templates/' . $filename;

		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}

		if ( strpos( $file, '.php' ) !== false ) {
			ob_start();
			require $file;
			$contents = ob_get_clean();
		} else {
			$contents = file_get_contents( $file );
		}

		if ( !empty($args) && is_array($args) ) {
			foreach($args as $fieldname => $value ) {
				$contents = str_replace( '{'.$fieldname.'}', $value, $contents );
			}
		}

		return $contents;
	}
}

if ( ! function_exists( 'burst_array_filter_multidimensional' ) ) {
	function burst_array_filter_multidimensional(
		$array, $filter_key, $filter_value
	) {
		$new = array_filter( $array,
			function ( $var ) use ( $filter_value, $filter_key ) {
				return isset( $var[ $filter_key ] ) ? ( $var[ $filter_key ]
				                                        == $filter_value )
					: false;
			} );

		return $new;
	}
}


add_action( 'wp_ajax_burst_get_posts', 'burst_get_posts_ajax_callback' ); // wp_ajax_{action}
function burst_get_posts_ajax_callback(){
 
	$return = array();
 
	$search_results = new WP_Query( array( 
		's'=> $_GET['q'], 
		'post_status' => 'publish',
		'ignore_sticky_posts' => 1,
		'posts_per_page' => 50
	) );
	if( $search_results->have_posts() ) :
		while( $search_results->have_posts() ) : $search_results->the_post();	
			// shorten the title a little
			$title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
			$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
		endwhile;
	endif;
	echo json_encode( $return );
	die;
}

if ( ! function_exists( 'burst_localize_date' ) ) {

	function burst_localize_date( $date ) {
		$month             = date( 'F', strtotime( $date ) ); //june
		$month_localized   = __( $month ); //juni
		$date              = str_replace( $month, $month_localized, $date );
		$weekday           = date( 'l', strtotime( $date ) ); //wednesday
		$weekday_localized = __( $weekday ); //woensdag
		$date              = str_replace( $weekday, $weekday_localized, $date );

		return $date;
	}
}

/**
 * Generate a random string, using a cryptographically secure 
 * pseudorandom number generator (random_int)
 *
 * This function uses type hints now (PHP 7+ only), but it was originally
 * written for PHP 5 as well.
 * 
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 * 
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */

if ( ! function_exists( 'burst_random_str' ) ) {
	function burst_random_str(
	    int $length = 64,
	    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	): string {
	    if ($length < 1) {
	        throw new \RangeException("Length must be a positive integer");
	    }
	    $pieces = [];
	    $max = mb_strlen($keyspace, '8bit') - 1;
	    for ($i = 0; $i < $length; ++$i) {
	        $pieces []= $keyspace[random_int(0, $max)];
	    }
	    return implode('', $pieces);
	}
}

if ( ! function_exists( 'burst_get_active_experiments_id' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param array $args
	 *
	 * @return stdClass Object
	 */

	function burst_get_active_experiments_id( $args = array() ) {
		// $args = wp_parse_args( $args, array( 'status' => 'active' ) );
		$sql  = '';
		global $wpdb;
		// if ( $args['status'] === 'archived' ) {
		// 	$sql = 'AND cdb.archived = true and cdb.test_running = true';
		// }

		$experiments
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_experiments where test_running = 1" );
		if (!empty($experiments)){
			return $experiments;	
		} else {
			return array();
		}
		
	}
}

if ( ! function_exists( 'burst_post_has_experiment' ) ) {

	/**
	 * Check if post has experiment attached
	 * @param $post_id
	 *
	 * @return Boolean
	 */
	
	function burst_post_has_experiment($post_id = false){
		if (!$post_id) return;

		$experiment_id = get_post_meta($post_id, 'burst_experiment_id');
		$has_experiment = intval($experiment_id) ? true : false;

		return $has_experiment;
	}

}

if ( ! function_exists( 'burst_get_experiment_id_for_post' ) ) {

	/**
	 * Check if post has experiment attached
	 * @param $post_id
	 *
	 * @return Boolean
	 */
	
	function burst_get_experiment_id_for_post($post_id = false){

		if (!$post_id) return;

		$experiment_id = get_post_meta($post_id, 'burst_experiment_id');

		return $experiment_id;
	}

}

