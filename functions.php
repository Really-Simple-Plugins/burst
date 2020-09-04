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

if ( ! function_exists( 'burst_get_ab_tests' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param array $args
	 *
	 * @return stdClass Object
	 */

	function burst_get_ab_tests( $args = array() ) {
		$args = wp_parse_args( $args, array( 'status' => 'active' ) );
		$sql  = '';
		global $wpdb;
		if ( $args['status'] === 'archived' ) {
			$sql = 'AND cdb.archived = true';
		}
		if ( $args['status'] === 'active' ) {
			$sql = 'AND cdb.archived = false';
		}

		$ab_tests
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_ab_tests as cdb where 1=1 $sql" );

		return $ab_tests;
	}
}

if ( ! function_exists( 'burst_get_ab_tests_by' ) ) {

	/**
	 * Get array of banner objects
	 *
	 * @param string     $field The field to retrieve the user with. id | ID | title | variant_id | control_id | date_created
	 * @param int|string $value A value for $field. ID | title | date
	 *	 *
	 * @return stdClass Object
	 */

	function burst_get_ab_tests_by( $field, $value ) {
		global $wpdb;

		$ab_tests
			= $wpdb->get_results( "select * from {$wpdb->prefix}burst_ab_tests as cdb where {$field} = {$value}" );

		return $ab_tests;
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