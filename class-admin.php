<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
if ( ! class_exists( "burst_admin" ) ) {
	class burst_admin {
		private static $_this;
		public $error_message = "";
		public $success_message = "";
		public $task_count = 0;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			add_action( 'admin_enqueue_scripts',
				array( $this, 'enqueue_assets' ) );
			add_action( 'admin_menu', array( $this, 'register_admin_page' ),
				20 );

			$plugin = burst_plugin;
			add_filter( "plugin_action_links_$plugin",
				array( $this, 'plugin_settings_link' ) );
			//multisite
			add_filter( "network_admin_plugin_action_links_$plugin",
				array( $this, 'plugin_settings_link' ) );
			add_action( 'admin_init', array( $this, 'check_upgrade' ), 10, 2 );
			add_action( 'burst_show_message', array( $this, 'show_message' ) );


			add_action('admin_init',array( $this, 'create_variant' ) );
            add_action('add_meta_boxes', array( $this, 'add_variant' ));


		}

		static function this() {
			return self::$_this;
		}


		function add_variant($post_type)
		{
			if (!current_user_can('edit_posts')) return;

			add_meta_box('burst_edit_meta_box', __('Burst Split AB testing', 'burst'), array($this, 'show_proposal_metabox'), null, 'side', 'high', array(
				//'__block_editor_compatible_meta_box' => true,
			));
		}


		/**
		 *
		 * click "create" button
		 * copy post to "variant" status
		 *
		 *
		 *
		 */

		public function show_proposal_metabox(){

		    if (!current_user_can('edit_posts')) return;

			global $post;
			//get posts with status variant with burst_variant_parent = this post id
			//check if this post has a proposal waiting or is a proposal
			?>
            <form method="POST">
                <?php wp_nonce_field('burst_create_variant', 'burst_create_variant_nonce' )?>
                <input type="hidden" name="burst_create_variant_id" value="<?php echo $post->ID?>">
                <input type="submit" class="button-primary" value="<?php _e("Create AB test", "burst")?>">
            </form>
			<?php
		}


		/**
		 * Function for post duplication. Dups appear as drafts. User is redirected to the edit screen
		 *
		 *
		 */
		public function create_variant()
		{
			if (!current_user_can('edit_posts')) return;

			//if (!isset($_POST["burst_create_variant_id"]) && !isset($_POST['burst_create_variant_nonce']) && !wp_verify_nonce( $_POST['burst_create_variant_nonce'], 'burst_create_variant')) return;
			if (!isset($_POST["burst_create_variant_id"])) return;


			global $wpdb;

			$post_id = intval($_POST["burst_create_variant_id"]);

			/*
			 *  all the original post data then
			 */
			$post = get_post($post_id);

			$current_user = wp_get_current_user();
			$new_post_author = $current_user->ID;

			/*
			 * if post data exists, create the post duplicate
			 */
			error_log('clicked');

			if (isset($post) && $post != null) {
				error_log('isset');
				/*
				 * new post data array
				 */
				$args = array(
					'comment_status' => $post->comment_status,
					'ping_status' => $post->ping_status,
					'post_author' => $new_post_author,
					'post_content' => $post->post_content,
					'post_excerpt' => $post->post_excerpt,
					'post_name' => $post->post_name,
					'post_parent' => $post->post_parent,
					'post_password' => $post->post_password,
					'post_title' => $post->post_title,
					'post_type' => $post->post_type,
					'to_ping' => $post->to_ping,
					'menu_order' => $post->menu_order
				);

				/*
				 * insert the post by wp_insert_post() function
				 */

				$new_post_id = wp_insert_post($args);
				add_post_meta($new_post_id,'burst_variant_parent', $post_id );
				add_post_meta($post_id,'burst_variant_child', $new_post_id );

				error_log($new_post_id);

				/*
				 * get all current post terms ad set them to the new post draft
				 */
				$taxonomies = get_object_taxonomies($post->post_type); // returns array of taxonomy names for post type, ex array("category", "post_tag");
				foreach ($taxonomies as $taxonomy) {
					$post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
					wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
				}

				/*
				 * duplicate all post meta just in two SQL queries
				 */

				$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
				if (count($post_meta_infos) != 0) {
					$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
					foreach ($post_meta_infos as $meta_info) {
						$meta_key = $meta_info->meta_key;
						if ($meta_key == '_wp_old_slug') continue;
						$meta_value = addslashes($meta_info->meta_value);
						$sql_query_sel[] = "SELECT $new_post_id, '$meta_key', '$meta_value'";
					}
					$sql_query .= implode(" UNION ALL ", $sql_query_sel);
					$wpdb->query($sql_query);
				}
			}
			// redirect post=2&action=edit
			$url = get_admin_url().'post.php?post='.$new_post_id.'&action=edit';
			error_log($url);
			if ( wp_redirect( $url ) ) {
			    exit;
			}
		}

		public function process_variant_submit(){

			if (!current_user_can('edit_posts')) return;

			if (isset($_POST['view_proposal_id'])){
				$post_id = intval($_POST['view_proposal_id']);
				//redirect to posst id

			}


		}


		/**
		 * Do upgrade on update
		 */

		public function check_upgrade() {
			//when debug is enabled, a timestamp is appended. We strip this for version comparison purposes.
			$prev_version = get_option( 'burst-current-version', false );

			//set a default region if this is an upgrade:
			if ( $prev_version
			     && version_compare( $prev_version, '2.0.1', '<' )
			) {
                //upgrade
			}

			do_action( 'burst_upgrade', $prev_version );

			update_option( 'burst-current-version', burst_version );
		}

		/**
		 * enqueue some assets
		 * @param $hook
		 */


		public function enqueue_assets( $hook ) {
			if ( strpos( $hook, 'burst' ) === false
			) {
				return;
			}
			wp_register_style( 'burst',
				trailingslashit( burst_url ) . 'assets/css/style.css', "",
				burst_version );
			wp_enqueue_style( 'burst' );

			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? ''
				: '.min';
			wp_enqueue_script( 'burst-admin',
				burst_url . "assets/js/admin$minified.js",
				array( 'jquery' ), burst_version, true );

			wp_localize_script(
				'burst-admin',
				'burst',
				array(
					'admin_url'    => admin_url( 'admin-ajax.php' ),
				)
			);
		}

		/**
		 * Add custom link to plugins overview page
		 *
		 * @hooked plugin_action_links_$plugin
		 *
		 * @param array $links
		 *
		 * @return array $links
		 */

		public function plugin_settings_link( $links ) {
			$settings_link = '<a href="'
			                 . admin_url( "admin.php?page=burst" )
			                 . '" class="burst-settings-link">'
			                 . __( "Settings", 'burst' ) . '</a>';
			array_unshift( $links, $settings_link );

			$support_link = defined( 'burst_free' )
				? "https://wordpress.org/support/plugin/burst"
				: "https://wpburst.com/support";
			$faq_link     = '<a target="_blank" href="' . $support_link . '">'
			                . __( 'Support', 'burst' ) . '</a>';
			array_unshift( $links, $faq_link );

			if ( ! defined( 'burst_premium' ) ) {
				$upgrade_link
					= '<a style="color:#2DAAE1;font-weight:bold" target="_blank" href="https://wpburst.com/l/pricing">'
					  . __( 'Upgrade to premium', 'burst' ) . '</a>';
				array_unshift( $links, $upgrade_link );
			}

			return $links;
		}

		/**
         *  get list of warnings for the tool
         *
		 * @param bool $cache
		 *
		 * @return array
		 */

		public function get_warnings($cache = false) {
		    return array('warning-one');
        }


		/**
		 * Register admin page
		 */

		public function register_admin_page() {
			if ( ! current_user_can('manage_options') ) {
				return;
			}

			$warnings      = $this->get_warnings( true );
			$warning_count = count( $warnings );
			$warning_title = esc_attr( sprintf( '%d plugin warnings',
				$warning_count ) );
			$menu_label    = sprintf( __( 'Burst %s', 'burst' ),
				"<span class='update-plugins count-$warning_count' title='$warning_title'><span class='update-count'>"
				. number_format_i18n( $warning_count ) . "</span></span>" );


			global $burst_admin_page;
			$burst_admin_page = add_menu_page(
				__( 'Burst', 'burst' ),
				$menu_label,
				'manage_options',
				'burst',
				array( $this, 'main_page' ),
				burst_url . 'assets/images/menu-icon.png',
				burst_MAIN_MENU_POSITION
			);

			add_submenu_page(
				'burst',
				__( 'Dashboard', 'burst' ),
				__( 'Dashboard', 'burst' ),
				'manage_options',
				'burst',
				array( $this, 'main_page' )
			);

			do_action( 'burst_admin_menu' );

			if ( defined( 'burst_free' ) && burst_free ) {
				global $submenu;
				$class                  = 'burst-submenu';
				$highest_index = count($submenu['burst']);
				$submenu['burst'][] = array(
						__( 'Upgrade to premium', 'burst' ),
						'manage_options',
						'https://wpburst.com/pricing'
				);
				if ( isset( $submenu['burst'][$highest_index] ) ) {
					if (! isset ($submenu['burst'][$highest_index][4])) $submenu['burst'][$highest_index][4] = '';
					$submenu['burst'][$highest_index][4] .= ' ' . $class;
				}
			}

		}

		/**
		 * Main settings page
		 */

		public function main_page() {
			?>
			<div class="wrap" id="burst">
				<div class="dashboard">
					<h1>Burst</h1>
				</div>
			</div>
			<?php
		}




		public function settings() {
			?>
			<div class="wrap burst-settings">
				<h1><?php _e( "Settings" ) ?></h1>
				<?php do_action( 'burst_show_message' ) ?>
				<form action="" method="post" enctype="multipart/form-data">

					<table class="form-table">
						<?php
						BURST::$field->get_fields( 'settings' );
						BURST::$field->save_button();
						?>

					</table>
				</form>
			</div>
			<?php
		}


		/**
		 * Get the html output for a help tip
		 *
		 * @param $str
		 */

		public function get_help_tip( $str ) {
			?>
			<span class="burst-tooltip-right tooltip-right"
			      data-burst-tooltip="<?php echo $str ?>">
              <span class="dashicons dashicons-editor-help"></span>
            </span>
			<?php
		}

		public function send_mail( $message, $from_name, $from_email ) {
			$subject = "Support request from $from_name";
			$to      = "support@wpburst.com";
			$headers = array();
			add_filter( 'wp_mail_content_type', function ( $content_type ) {
				return 'text/html';
			} );

			$headers[] = "Reply-To: $from_name <$from_email>" . "\r\n";
			$success   = wp_mail( $to, $subject, $message, $headers );

			// Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
			remove_filter( 'wp_mail_content_type', 'set_html_content_type' );

			return $success;
		}


	}
} //class closure
