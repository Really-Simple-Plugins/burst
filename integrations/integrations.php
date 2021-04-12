<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

global $burst_integrations_list;
$burst_integrations_list = apply_filters( 'burst_integrations', array(
	'elementor' => array(
			'constant_or_function' => 'ELEMENTOR_VERSION',
			'label'                => 'Elementor Website Builder',
	),
	'wp-rocket' => array(
		'constant_or_function' => 'WP_ROCKET_VERSION',
		'label'                => 'WP Rocket',
	),
));

foreach ( $burst_integrations_list as $plugin => $details ) {

	if ( ! isset( $details['early_load'] ) ) {
		continue;
	}
	if ( ! file_exists( WP_PLUGIN_DIR . "/" . $plugin . "/" . $plugin
	                    . ".php" )
	) {
		continue;
	}

	$early_load = $details['early_load'];
	$file       = apply_filters( 'burst_early_load_path',
		burst_path . "integrations/plugins/$early_load", $details );

	if ( file_exists( $file ) ) {
		require_once( $file );
	} else {
		error_log( "searched for $plugin integration at $file, but did not find it" );
	}
}

/**
 * Check if a plugin from the integrations list is active
 * @param $plugin
 *
 * @return bool
 */
function burst_integration_plugin_is_active( $plugin ){
	global $burst_integrations_list;
	if ( !isset($burst_integrations_list[ $plugin ]) ) return false;

	//because we need a default, we don't use the get_value from burst. The fields array is not loaded yet, so there are no defaults
	$fields = get_option( 'burst_options_integrations' );
	$details = $burst_integrations_list[ $plugin ];
	$enabled = isset( $fields[ $plugin ] ) ? $fields[ $plugin ] : true;
	$theme = wp_get_theme();
	if (
			( defined($details['constant_or_function'])
			   || function_exists( $details['constant_or_function'] )
			   || class_exists( $details['constant_or_function'] )
			   || ( $theme && ($theme->name === $details['constant_or_function']) )
			)
	     	&& $enabled
	) {
		return true;
	}
	return false;
}

/**
 * code loaded without privileges to allow integrations between plugins and services, when enabled.
 */

function burst_integrations() {

	global $burst_integrations_list;
	foreach ( $burst_integrations_list as $plugin => $details ) {
		if ( burst_integration_plugin_is_active( $plugin ) ) {
			$file = apply_filters( 'burst_integration_path', burst_path . "integrations/plugins/$plugin.php", $plugin );
			if ( file_exists( $file ) ) {
				require_once( $file );
			} else {
				error_log( "searched for $plugin integration at $file, but did not find it" );
			}
		}
	}

}

add_action( 'plugins_loaded', 'burst_integrations', 10 );