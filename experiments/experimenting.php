<?php 
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
	
if ( ! class_exists( "burst_experimenting" ) ) {
	class burst_experimenting {
		private static $_this;
		public $experimenting_enabled = false;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			add_action( 'init', array($this, 'add_experiment_post_status') );
			add_filter( 'the_content', array($this, 'load_experiment_content') );
			add_action('wp_enqueue_scripts', array($this,'enqueue_assets') );
			add_action('admin_footer-post.php', array($this,'add_variant_status_add_in_post_page') );
		    add_action('admin_footer-post-new.php', array($this,'add_variant_status_add_in_post_page') );
		    add_action('admin_footer-edit.php', array($this,'add_variant_status_add_in_quick_edit') );
		    add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
		    add_action( 'wp_ajax_burst_experiment_action', array($this, 'experiment_action'));

			self::$_this = $this;
		}

		static function this() {
			return self::$_this;
		}

		/**
		 * Start or stop an experiment with an ajax request
		 *
		 */
		public function experiment_action(){
			$error = false;
			if ( ! burst_user_can_manage() ) {
				$error = true;
			}
			if ( !isset($_POST['experiment_id'])) {
				$error = true;
			}

			if ( !isset($_POST['type'])) {
				$error = true;
			}

			if ( !$error ) {
				$experiment_id = intval( $_POST['experiment_id'] );
				$experiment = new BURST_EXPERIMENT($experiment_id);
				if ( $_POST['type'] === 'start' ) {
					$experiment->start();
				} else {
					$experiment->stop();
				}
			}

			$return  = array(
				'success' => !$error,
			);
			echo json_encode( $return );
			die;
		}

		/**
		 * Enqueue some assets
		 * @param $hook
		 */
		public function enqueue_assets( $hook ) {
			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			global $post;
			//set some defaults
			$localize_args = array(
				'url' => get_rest_url() . 'burst/v1/hit',
				'goal' => 'visit',
				'goal_identifier' => ''
			);

			if ( $post ) {
				$experiment = new BURST_EXPERIMENT(false, $post->ID );
				if ($experiment->id) {
					$localize_args['goal'] = $experiment->goal;
					$localize_args['goal_identifier'] = $experiment->goal_identifier;
					$localize_args['goal'] = 'visit';
					$localize_args['goal_identifier'] = 'class';
				}
			}

			wp_enqueue_script( 'burst',
				burst_url . "assets/js/burst$minified.js", array(),
				burst_version, true );
			wp_localize_script(
				'burst',
				'burst',
				$localize_args
			);
		}

		/**
		 * Load variant content by filtering the_content
		 * @param string $content
		 *
		 * @return string
		 */
		public function load_experiment_content($content){
			global $post;

			$experiment = new BURST_EXPERIMENT(false, $post->ID );
			//when this page is a goal
			if ( $experiment->goal_id == $post->ID ) {
				$content .= '<script type="text/javascript">var burst_experiment_id = "' . $experiment->id . '";var burst_is_goal_page = true;</script>';
			} else if ( $experiment->id && $experiment->variant_id ) {
				$burst_uid     = isset( $_COOKIE['burst_uid'] ) ? sanitize_text_field( $_COOKIE['burst_uid'] ) : false;
				$page_url      = burst_get_current_url();
				$test_version  = false;

				if ( $burst_uid ) {
					$test_version = BURST::$statistics->get_latest_visit_data( $burst_uid, $page_url, 'test_version' );
				}

				if ( ! $test_version ) {
					$choice = rand( 0, 1 );
					if ( $choice === 1 ) {
						$test_version = 'variant';
					} else {
						$test_version = 'control';
					}
				}

				if ( $test_version == 'variant' ) {
					$content = get_the_content( null, false, $experiment->variant_id );
				} else {
					$content = get_the_content( null, false, $experiment->control_id );
				}

				// $content = apply_filters( 'the_content', $content );
				// Causes infinite loop
				$content = str_replace( ']]>', ']]&gt;', $content );

				$content .= '<script type="text/javascript">
					var burst_test_version = "' . $test_version . '";
					var burst_experiment_id = ' . $experiment->id . ';
					var burst_goal_identifier = "' . $experiment->goal_identifier . '";
					</script>';
			}
			return $content;
		}

		/**
		* Add a post display state for Experiments in the page list table.
		*
		* @param array $post_states An array of post display states.
		* @param \WP_Post $post The current post object.
		*
		* @return mixed
		*/
		
		function add_display_post_states( $post_states, $post ) {
			if ($post->post_status == 'experiment') {
				$post_states[ 'Experiment' ] = __('Experiment', 'burst');
			}
        	
			return $post_states;
		}


		/**
		 * Add 'Experiment' post status.
		 */
		function add_experiment_post_status(){
			register_post_status( 'experiment', array(
				'label'                     => __( 'Experiment', 'burst' ),
				'public'                    => true,
				'internal'					=> true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,

				'label_count'               => _n_noop( 'Experiment <span class="count">(%s)</span>', 'Experiments <span class="count">(%s)</span>' , 'burst'),
			) );
			
		}

	 	function add_variant_status_add_in_quick_edit() {
	        echo "	<script>
				        jQuery(document).ready( function() {
				            jQuery( 'select[name=\"_status\"]' ).append( '<option value=\"experiment\">". __('Experiment',"burst")."</option>' );      
				        }); 
			        </script>";
	    }
	    
	    function add_variant_status_add_in_post_page() {
	        echo "	<script>
				        jQuery(document).ready( function($) {        
				            $( 'select[name=\"post_status\"]' ).append( '<option value=\"experiment\">". __('Experiment',"burst")."</option>' );
				            if ($('#hidden_post_status').val()=== 'experiment') {
				            	$('#post_status').val('experiment');
				            }

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
add_action( 'plugins_loaded', 'burst_experiment_form_submit', 20 );
function burst_experiment_form_submit() {
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
		$experiment = new BURST_EXPERIMENT();
	} else {
		$id     = intval( $_GET['id'] );
		$experiment = new BURST_EXPERIMENT( $id );
	}
	$experiment->process_form( $_POST );
}
