<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

	add_action( 'admin_init', 'burst_redirect_to_ab_test' );
	function burst_redirect_to_ab_test() {
		//on ab_test page?
		if ( ! isset( $_GET['page'] ) || $_GET['page'] != 'burst-ab_tests' ) {
			return;
		}
		if ( ! apply_filters( 'burst_show_ab_test_list_view', false )
		     && ! isset( $_GET['id'] )
		) {
			wp_redirect( add_query_arg( 'id', burst_get_default_banner_id(),
				admin_url( 'admin.php?page=burst-ab-tests' ) ) );
		}
	}
