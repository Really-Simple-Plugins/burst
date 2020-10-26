<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
	
if ( ! class_exists( "burst_ab_testing" ) ) {
	class burst_ab_testing {
		private static $_this;
		public $ab_testing_enabled = false;


		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			add_action( 'init', array($this, 'add_variant_post_status') );
			add_filter( 'the_content', array($this, 'load_variant_content') );

			add_action('wp_enqueue_scripts', array($this,'enqueue_assets') );
			
			add_action('admin_footer-post.php', array($this,'add_variant_status_add_in_post_page') );
		    add_action('admin_footer-post-new.php', array($this,'add_variant_status_add_in_post_page') );
		    add_action('admin_footer-edit.php', array($this,'add_variant_status_add_in_quick_edit') );

			self::$_this = $this;
		}

		static function this() {
			return self::$_this;
		}


		public function enqueue_assets( $hook ) {
			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_script( 'burst',
				burst_url . "assets/js/burst$minified.js", array(),
				burst_version, true );
			wp_localize_script(
				'burst',
				'burst',
				array( 'url' => site_url('wp-json/burst/v1/hit'))
			);
			
		}
		/**
		 *
		 * //if ab testing enabled
		 * //if a post is loaded, check if it has variants.
		 * get content of variant and load instead of post.
		 *
		 *
		 *
		 */


		/**
		 * Load variant content
		 * @param string $content
		 *
		 * @return mixed
		 */
		public function load_variant_content($content){
			global $post;
			global $ab_testing_enabled;
			//if ab enabled
			$ab_testing_enabled = true;
			if (!$ab_testing_enabled) return $content;
			
			//if has variant
			//get content
			$post_id = $post->ID;
			$burst_variant_child_id = get_post_meta($post_id, 'burst_variant_child', true);
			if (!intval($burst_variant_child_id)) return;

			$choice = rand(0,1);
			if ($choice === 1) {
				$content = get_the_content(null, false, $burst_variant_child_id);
				
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				// update_post_meta($variant_post_id, 'burst_hits', get_post_meta($variant_post_id,'burst_hits')+1);
				error_log('A');

			} else {
				// update_post_meta($parent_post_id, 'burst_hits', get_post_meta($parent_post_id,'burst_hits')+1);
				error_log('B');

			}

			//check if this user has a cookie 
			// $burst_uid = isset( $_COOKIE['burst_uid']) ? $_COOKIE['burst_uid'] : false;
			// setcookie('burst_uid', $burst_uid, time() + apply_filters('burst_cookie_retention', DAY_IN_SECONDS * 365), '/');

			return $content;
		}


		/**
		 * Add 'variant' post status.
		 */
		function add_variant_post_status(){
			register_post_status( 'variant', array(
				'label'                     => __( 'Variant', 'burst' ),
				'public'                    => false,
				'exclude_from_search'       => true,
				'show_in_admin_all_list'    => false,
				'show_in_admin_status_list' => false,
				'label_count'               => _n_noop( 'Unread <span class="count">(%s)</span>', 'Unread <span class="count">(%s)</span>' , 'burst'),
			) );
		}

	 	function add_variant_status_add_in_quick_edit() {
	        echo "	<script>
				        jQuery(document).ready( function() {
				            jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"Variant\">Variant</option>' );      
				        }); 
			        </script>";
	    }
	    
	    function add_variant_status_add_in_post_page() {
	        echo "	<script>
				        jQuery(document).ready( function() {        
				            jQuery( 'select[name=\"post_status\"]' ).append( '<option value=\"Variant\">Variant</option>' );
				        });
			        </script>";
	    }
	   

	} //class closure
} //class_exists closure

/**
 * This function is hooked to the plugins_loaded, prio 10 hook, as otherwise there is some escaping we don't want.
 *
 * @todo fix the escaping
 */
add_action( 'plugins_loaded', 'burst_ab_test_form_submit', 20 );
function burst_ab_test_form_submit() {
	if ( ! burst_user_can_manage() ) {
		return;
	}

	if ( ! isset( $_GET['id'] ) && ! isset( $_POST['burst_add_new'] ) ) {
		return;
	}

	if ( ! isset( $_POST['burst_nonce'] ) ) {
		return;
	}

	if ( isset( $_POST['burst_add_new'] ) ) {
		$ab_test = new BURST_AB_TEST();
	} else {
		$id     = intval( $_GET['id'] );
		$ab_test = new BURST_AB_TEST( $id );
	}
	$ab_test->process_form( $_POST );

	if ( isset( $_POST['burst_add_new'] ) ) {
		wp_redirect( admin_url( 'admin.php?page=burst-ab_test&id='
		                        . $ab_test->id ) );
		exit;
	}
}

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