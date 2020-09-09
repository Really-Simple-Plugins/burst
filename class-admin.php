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


			add_action( 'admin_init',array( $this, 'create_variant_from_post' ) );
            add_action( 'add_meta_boxes', array( $this, 'add_variant' ));


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
			$ab_tests = burst_get_ab_tests_by('control_id', $post->ID) ? burst_get_ab_tests_by('control_id', $post->ID) : burst_get_ab_tests_by('variant_id', $post->ID);
			if ($ab_tests) {
				foreach ($ab_tests as $ab_test) {
					$variant_id = $ab_test->variant_id;
					$variant = get_post($variant_id);
					$control_id = $ab_test->control_id;
					$control = get_post($control_id);

					$html = 
					$html = $control->post_title.'(control) vs '. $variant->post_title.'(variant)';
					echo $html;
				}

			} else {
				?>
           		<form method="POST">
                <?php wp_nonce_field('burst_create_variant', 'burst_create_variant_nonce' )?>
                <input type="hidden" name="burst_create_variant_id" value="<?php echo $post->ID?>">
                <input type="submit" class="button-primary" value="<?php _e("Create AB test", "burst")?>">
            	</form>
				<?php
			}
			
		}

		/**
		 * Function for post duplication. Dups appear as drafts. User is redirected to the edit screen
		 *
		 *
		 */
		public function create_variant_from_post()
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
				 * create new slug
				 */
				if (isset($post->post_name)) { 
					$slug = $post->post_name . '_' . __( "variation", 'burst' );
				} else {
					$slug = __( "variation", 'burst' );
				}

				/*
				 * new post data array
				 */
				$args = array(
					'comment_status' => $post->comment_status,
					'ping_status' => 'variant',
					'post_author' => $new_post_author,
					'post_content' => $post->post_content,
					'post_excerpt' => $post->post_excerpt,
					'post_name' => $slug,
					'post_parent' => $post->post_parent,
					'post_password' => $post->post_password,
					'post_title' => $post->post_title,
					'post_slug' => $post->post_title,
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

				/*
				* create database entry
				*/

				$ab_test = new BURST_AB_TEST();
				$ab_test->archived = false;
				$ab_test->title = $post_title;
				$ab_test->control_id = $post_id;
				$ab_test->variant_id = $new_post_id;
				$ab_test->test_running = false;
				$ab_test->date_created = date("Y-m-d h:i:sa");
				$ab_test->save();

				add_post_meta( $post_id,'contains_tests', true );
				

			}
			// redirect to duplicated post also known as the variant
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
			     && version_compare( $prev_version, '1.0.0', '<' )
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

			wp_register_style( 'burst-admin',
				trailingslashit( burst_url ) . "assets/css/admin$minified.css", "",
				burst_version );
			wp_enqueue_style( 'burst-admin' );

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

			// if ( ! defined( 'burst_premium' ) ) {
			// 	$upgrade_link
			// 		= '<a style="color:#2DAAE1;font-weight:bold" target="_blank" href="https://wpburst.com/l/pricing">'
			// 		  . __( 'Upgrade to premium', 'burst' ) . '</a>';
			// 	array_unshift( $links, $upgrade_link );
			// }

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
				burst_url . 'assets/images/menu-icon.svg',
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

			add_submenu_page(
				'burst',
				__( 'AB tests', 'burst' ),
				__( 'AB tests', 'burst' ),
				'manage_options',
				'burst-ab-tests',
				array( $this, 'ab_tests_overview' )
			);

			add_submenu_page(
				'burst',
				__( 'Settings' ),
				__( 'Settings' ),
				'manage_options',
				"burst-settings",
				array( $this, 'settings' )
			);

			do_action( 'burst_admin_menu' );

			// if ( defined( 'burst_free' ) && burst_free ) {
			// 	global $submenu;
			// 	$class                  = 'burst-submenu';
			// 	$highest_index = count($submenu['burst']);
			// 	$submenu['burst'][] = array(
			// 			__( 'Upgrade to premium', 'burst' ),
			// 			'manage_options',
			// 			'https://wpburst.com/pricing'
			// 	);
			// 	if ( isset( $submenu['burst'][$highest_index] ) ) {
			// 		if (! isset ($submenu['burst'][$highest_index][4])) $submenu['burst'][$highest_index][4] = '';
			// 		$submenu['burst'][$highest_index][4] .= ' ' . $class;
			// 	}
			// }

		}

		/**
		 * Main settings page
		 */

		public function main_page() {
			$grid_items =
				array(
					array(
						'header' => __("Your progress", "burst"),
						'body'  => 'progress',
						'footer' => 'footer',
						'class' => '',
						'page' => 'dashboard',
						'controls' => sprintf(__("Remaining tasks (%s)", "burst"), count( $this->get_warnings() )),
					),
					array(
						'header' => __("Documents", "burst"),
						'body'  => 'documents',
						'footer' => 'footer',
						'class' => 'small',
						'page' => 'dashboard',
						'controls' => __("Last update", "burst"),
					),

					array(
						'header' => __("Tools", "burst"),
						'body'  => 'tools',
						'footer' => 'footer',
						'class' => 'small',
						'page' => 'dashboard',
						'controls' => '',
					),
					array(
	                    'header' => __("Tips & Tricks", "burst"),
	                    'body' => 'tipstricks',
	                    'footer' => 'footer',
	                    'class' => 'half-height burst-tips-tricks',
	                    'page' => 'dashboard',
	                    'controls' => '',
	                ),
	                array(
	                    'header' => __("Our Plugins", "burst"),
	                    'body' => 'upsell-element',
	                    'footer' => 'footer',
	                    'class' => 'half-height no-border no-background upsell-grid-container upsell',
	                    'page' => 'dashboard',
	                    'controls' => '<div class="rsp-logo"><a href="https://really-simple-plugins.com/"><img src="'. trailingslashit(burst_url) .'assets/images/really-simple-plugins.png" /></a></div>',
	                ),
				);

			//give each item the key as index
			array_walk($grid_items, function(&$a, $b) { $a['index'] = $b; });

			$grid_html = '';
			foreach ($grid_items as $index => $grid_item) {
				$grid_html .= burst_grid_element($grid_item);
			}
			$args = array(
				'page' => 'dashboard',
				'content' => burst_grid_container($grid_html),
			);
			echo burst_get_template('admin_wrap.php', $args );
		}

		function ab_tests_overview() {

			if ( ! burst_user_can_manage() ) {
				return;
			}

			/*
			 * Reset the statistics
			 * */
			if ( class_exists( 'burst_statistics' )
			     && ( isset( $_GET['action'] )
			          && $_GET['action'] == 'reset_statistics' )
			) {
				BURST::$statistics->init_statistics();
			}

			$id = false;
			if ( isset( $_GET['id'] ) ) {
				$id = intval( $_GET['id'] );
			}

			if ( $id || ( isset( $_GET['action'] ) && $_GET['action'] == 'new' ) ) {
				include( dirname( __FILE__ ) . "/ab-tests/edit.php" );
			} else {

				include( dirname( __FILE__ ) . '/ab-tests/class-ab-test-table.php' );

				$ab_tests_table = new burst_ab_test_Table();
				$ab_tests_table->prepare_items();

				?>
				<div class="wrap cookie-warning">
					<h1><?php _e( "AB tests", 'burst' ) ?>
						<?php //do_action( 'burst_after_cookiebanner_title' ); ?>
					</h1>

					<form id="burst-ab_test-filter" method="get"
					      action="">

						<?php
						$ab_tests_table->search_box( __( 'Filter', 'burst' ),
							'burst-ab_test' );
						$ab_tests_table->display();
						?>
						<input type="hidden" name="page" value="burst-ab_test"/>
					</form>
					<?php //do_action( 'burst_after_cookiebanner_list' ); ?>
				</div>
				<?php
			}
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

		public function get_template($file, $path = burst_path, $args = array())
        {

            $file = trailingslashit($path) . 'templates/' . $file;
            $theme_file = trailingslashit(get_stylesheet_directory()) . dirname(burst_path) . $file;

            if (file_exists($theme_file)) {
                $file = $theme_file;
            }

            if (isset($args['tooltip'])) {
                $args['tooltip'] = BURST::$help->get_title_help_tip($args['tooltip']);
            } else {
	            $args['tooltip'] = '';
            }

            if (strpos($file, '.php') !== false) {
                ob_start();
                require $file;
                $contents = ob_get_clean();
            } else {
                $contents = file_get_contents($file);
            }

	        if (isset($args['type']) && ($args['type'] === 'settings' || $args['type'] === 'license')) {
		        $form_open =  '<form action="'.esc_url( add_query_arg(array('burst_redirect_to' => sanitize_title($args['type'])), admin_url( 'options.php' ))).'" method="post">';
                $form_close = '</form>';
		        $button = burst_save_button();
		        $contents = str_replace('{content}', $form_open.'{content}'.$button.$form_close, $contents);

	        }

            foreach ($args as $key => $value ){
                $contents = str_replace('{'.$key.'}', $value, $contents);
            }



	        return $contents;
        }

	    /**
         * Get status link for plugin, depending on installed, or premium availability
	     * @param $item
	     *
	     * @return string
	     */

        public function get_status_link($item){
            if (is_multisite()){
                $install_url = network_admin_url('plugin-install.php?s=');
            } else {
                $install_url = admin_url('plugin-install.php?s=');
            }

	        if (defined($item['constant_free']) && defined($item['constant_premium'])) {
		        $status = __("Installed", "wp-search-insights");
	        } elseif (defined($item['constant_free']) && !defined($item['constant_premium'])) {
		        $link = $item['website'];
		        $text = __('Upgrade to pro', 'wp-search-insights');
		        $status = "<a href=$link>$text</a>";
	        } else {
		        $link = $install_url.$item['search']."&tab=search&type=term";
		        $text = __('Install', 'wp-search-insights');
		        $status = "<a href=$link>$text</a>";
	        }
	        return $status;
        }

	}
} //class closure
