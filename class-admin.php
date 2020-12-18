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

			add_action('admin_init', array($this, 'init_grid') );
			add_action('wp_ajax_burst_get_datatable', array($this, 'ajax_get_datatable'));


			add_action( 'admin_init',array( $this, 'duplicate_post_and_create_experiment' ) );
			add_action ( 'admin_init', array($this, 'hide_wordpress_and_other_plugin_notices') );
            add_action( 'add_meta_boxes', array( $this, 'add_burst_metabox_to_classic_editor' ) );

            add_filter( 'display_post_states', array( $this, 'add_display_post_states' ), 10, 2 );
            
		}

		static function this() {
			return self::$_this;
		}


		function add_burst_metabox_to_classic_editor($post_type)
		{
			if (!current_user_can('edit_posts')) return;
			if ($post_status == 'experiment') {
				add_meta_box('burst_edit_meta_box', __(burst_plugin_name, 'burst'), array($this, 'show_burst_experiment_metabox'), null, 'side', 'high', array(
					//'__block_editor_compatible_meta_box' => true,
				));
			} else {
				add_meta_box('burst_edit_meta_box', __(burst_plugin_name, 'burst'), array($this, 'show_burst_metabox'), null, 'side', 'high', array(
					//'__block_editor_compatible_meta_box' => true,
				));
			}
			
		}

		/**
		 *
		 * click "create" button
		 * copy post to "variant" status
		 *
		 *
		 *
		 */

		public function show_burst_metabox(){

		    if (!current_user_can('edit_posts')) return;

			global $post;
			$experiments = burst_get_experiments_by('control_id', $post->ID) ? burst_get_experiments_by('control_id', $post->ID) : burst_get_experiments_by('variant_id', $post->ID);
			if ($experiments) {
				foreach ($experiments as $experiment) {
					$variant_id = $experiment->variant_id;
					$variant = get_post($variant_id);
					$control_id = $experiment->control_id;
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
                <input type="submit" class="button-primary" name="burst_create_experiment" burst_create_variant_id value="<?php _e("Create experiment", "burst")?>">
            	</form>
				<?php
			}
			
		}

		public function show_burst_experiment_metabox(){

		    if (!current_user_can('edit_posts')) return;

			global $post;
			echo "Pannekoek";
			
		}

		/**
		 * Function for post duplication. Dups appear as drafts. User is redirected to the edit screen
		 *
		 */
		public function duplicate_post_and_create_experiment()
		{
			if (!current_user_can('edit_posts')) return;

			
			//if (!isset($_POST["burst_create_variant_id"]) && !isset($_POST['burst_create_variant_nonce']) && !wp_verify_nonce( $_POST['burst_create_variant_nonce'], 'burst_create_variant')) return;
			if (!isset($_POST["burst_create_experiment"]) || !isset($_POST["burst_create_variant_id"])) return; 


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
					$slug = $post->post_name . '_' . __( "experiment", 'burst' );
				} else {
					$slug = __( "experiment", 'burst' );
				}

				/*
				 * new post data array
				 */
				$args = array(
					'comment_status' => $post->comment_status,
					'ping_status' => 'experiment',
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

				$experiment = new BURST_EXPERIMENT();
				$experiment->archived = false;
				$experiment->title = $post_title;
				$experiment->control_id = $post_id;
				$experiment->variant_id = $new_post_id;
				$experiment->test_running = false;
				$experiment->date_created = date("Y-m-d h:i:sa");
				$experiment->save();

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
		* Add a post display state for special UM pages in the page list table.
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
				trailingslashit( burst_url ) . 'assets/css/admin.css', "",
				burst_version );
			wp_enqueue_style( 'burst' );

			//select2
			wp_register_style( 'select2',
					burst_url . 'assets/select2/css/select2.min.css', false,
					burst_version );
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2',
				burst_url . "assets/select2/js/select2.min.js",
				array( 'jquery' ), burst_version, true );

				//chartjs
			wp_register_style( 'chartjs',
					burst_url . 'assets/chartjs/Chart.min.css', false,
					burst_version );
			wp_enqueue_style( 'chartjs' );
			wp_enqueue_script( 'chartjs',
				burst_url . "assets/chartjs/Chart.min.js",
				array(), burst_version, true );

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
			$menu_label    = sprintf( __( burst_plugin_name, 'burst' ),
				"<span class='update-plugins count-$warning_count' title='$warning_title'><span class='update-count'>"
				. number_format_i18n( $warning_count ) . "</span></span>" );


			global $burst_admin_page;
			$burst_admin_page = add_menu_page(
				burst_plugin_name,
				$menu_label,
				'manage_options',
				'burst',
				array( $this, 'dashboard' ),
				burst_url . 'assets/images/menu-icon.svg',
				burst_main_menu_position
			);

			add_submenu_page(
				'burst',
				__( 'Dashboard', 'burst' ),
				__( 'Dashboard', 'burst' ),
				'manage_options',
				'burst',
				array( $this, 'dashboard' )
			);
			add_submenu_page(
				'burst',
				__( 'Insights', 'burst' ),
				__( 'Insights', 'burst' ),
				'manage_options',
				'burst-insights',
				array( $this, 'insights' )
			);

			add_submenu_page(
				'burst',
				__( 'Experiments', 'burst' ),
				__( 'Experiments', 'burst' ),
				'manage_options',
				'burst-experiments',
				array( $this, 'experiments_overview' )
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

		public function init_grid(){
		    $this->tabs = apply_filters('burst_tabs', array(
		            'dashboard' => array(
		                    'title'=> __( "General", "burst" ),
                    ),
		            'settings' => array(
			            'title'=> __( "Settings", "burst" ),
			            'capability' => 'manage_options',
		            ),
            ));

            $this->grid_items = array(
                1 => array(
                    'title' => __("Your last experiment", "burst"),
                    'content' => '<div class="burst-skeleton burst-skeleton-statistics"></div><canvas class="burst-chartjs-stats" width="400" height="400"></canvas>',
                    'class' => 'table-overview burst-load-ajax',
                    'type' => 'no-type',
                    'controls' => sprintf(__("Remaining tasks (%s)", "burst"), count( $this->get_warnings() )),
                    'can_hide' => true,
                    'page' => 'dashboard',
                    'body' => 'admin_wrap',

                ),
                2 => array(
                    'title' => __("Documents", "burst"),
                    'content' => '<div class="burst-skeleton"></div>',
                    'class' => 'small burst-load-ajax',
                    'type' => 'no-type',
                    'controls' => __("Last update", "burst"),
                    'can_hide' => true,
                    'ajax_load' => true,
                    'page' => 'dashboard',
                    'body' => 'admin_wrap',

                ),
                3 => array(
                    'title' => __("Tools", "burst"),
                    'content' => '<div class="burst-skeleton"></div>',
                    'class' => 'small burst-load-ajax',
                    'type' => 'no-type',
                    'controls' => '',
                    'can_hide' => true,
                    'ajax_load' => true,
                    'page' => 'dashboard',
                    'body' => 'admin_wrap',

                ),
                4 => array(
                    'title' => __("Tips & Tricks", "burst"),
                    'content' => $this->generate_tips_tricks(),
                    'type' => 'no-type',
                    'class' => 'half-height burst-tips-tricks',
                    'can_hide' => true,
                    'controls' => '',
                    'page' => 'dashboard',
                    'body' => 'admin_wrap',
                ),
                5 => array(
                    'title' => __("Our Plugins", "burst"),
                    'content' => $this->generate_other_plugins(),
                    'class' => 'half-height no-border no-background upsell-grid-container upsell',
                    'type' => 'no-type',
                    'can_hide' => false,
                    'controls' => '<div class="rsp-logo"><a href="https://really-simple-plugins.com/"><img src="'. trailingslashit(burst_url) .'assets/images/really-simple-plugins.png" /></a></div>',
                    'page' => 'dashboard',
                    'body' => 'admin_wrap',
                ),
                6 => array(
                    'title' => __("Pannekoek", "burst"),
                    'content' => '<div class="burst-skeleton burst-skeleton-statistics"></div>',
                    'class' => 'table-overview burst-load-ajax',
                    'type' => 'no-type',
                    'controls' => sprintf(__("Remaining tasks (%s)", "burst"), count( $this->get_warnings() )),
                    'can_hide' => true,
                    'page' => 'insights',
                    'body' => 'admin_wrap',

                ),

            );
        }

		/**
		 * Dashboard page
		 */

		public function dashboard() {

			$grid_items = $this->grid_items;
			//give each item the key as index
			array_walk($grid_items, function(&$a, $b) { $a['index'] = $b; });

			$grid_html = '';
			foreach ($grid_items as $index => $grid_item) {
				if($grid_item['page'] !== 'dashboard') continue;
				$grid_html .= burst_grid_element($grid_item);
			}
			$args = array(
				'page' => 'dashboard',
				'content' => burst_grid_container($grid_html),
			);
			echo burst_get_template('admin_wrap.php', $args );
		}

		/**
		 * Insights page
		 */

		public function insights() {

			$grid_items = $this->grid_items;
			//give each item the key as index
			array_walk($grid_items, function(&$a, $b) { $a['index'] = $b; });

			$grid_html = '';
			foreach ($grid_items as $index => $grid_item) {
				if($grid_item['page'] !== 'insights') continue;
				$grid_html .= burst_grid_element($grid_item);
			}
			$args = array(
				'page' => 'insights',
				'content' => burst_grid_container($grid_html),
			);
			echo burst_get_template('admin_wrap.php', $args );
		}


		function experiments_overview() {


			if ( ! burst_user_can_manage() ) {
				return;
			}

			$id = false;
			if ( isset( $_GET['id'] ) ) {
				$id = intval( $_GET['id'] );
			}

			ob_start();

			if ( $id || ( isset( $_GET['action'] ) && $_GET['action'] == 'new' ) ) {
				include( dirname( __FILE__ ) . "/experiments/edit.php" );
			} else {

				include( dirname( __FILE__ ) . '/experiments/class-experiment-table.php' );

				$experiments_table = new burst_experiment_Table();
				$experiments_table->prepare_items();

				?>

				<div class="wrap experiment">
					<h1><?php _e( "Your experiments", 'burst' ) ?>
						<?php //do_action( 'burst_after_experiment_title' ); ?>
						<a href="<?php echo admin_url('admin.php?page=burst-experiments&action=new'); ?>"
		                   class="page-title-action"><?php _e('Add experiment', 'burst') ?></a>
					</h1>

					<form id="burst-experiment-filter" method="get"
					      action="">

						<?php
						$experiments_table->search_box( __( 'Filter', 'burst' ),
							'burst-experiment' );
						$experiments_table->display();
						?>
						<input type="hidden" name="page" value="burst-experiment"/>
					</form>
					<?php //do_action( 'burst_after_experiment_list' ); ?>
				</div>
				<?php
			}
			$html = ob_get_clean();
			
			$args = array(
				'page' => 'experiments_overview',
				'content' => $html,
			);
			echo burst_get_template('admin_wrap.php', $args );
		}

		public function generate_other_plugins()
        {
            $items = array(
                1 => array(
                    'title' => '<div class="rsssl-yellow burst-bullet"></div>',
                    'content' => __("Really Simple SSL - Easily migrate your website to SSL"),
                    'link' => 'https://wordpress.org/plugins/really-simple-ssl/',
                    'class' => 'rsssl',
                    'constant_free' => 'rsssl_plugin',
                    'constant_premium' => 'rsssl_pro_plugin',
                    'website' => 'https://really-simple-ssl.com/pro',
                    'search' => 'Really+Simple+SSL+Mark+Wolters',
                ),
                2 => array(
                    'title' => '<div class="cmplz-blue burst-bullet"></div>',
                    'content' => __("Complianz Privacy Suite - Cookie Consent Management as it should be ", "burst"),
                    'link' => 'https://wordpress.org/plugins/complianz-gdpr/',
                    'class' => 'cmplz',
                    'constant_free' => 'cmplz_plugin',
                    'constant_premium' => 'cmplz_premium',
                    'website' => 'https://complianz.io/pricing',
                    'search' => 'complianz',
                ),
                3 => array(
                    'title' => '<div class="zip-pink burst-bullet"></div>',
                    'content' => __("Zip Recipes - Beautiful recipes optimized for Google ", "burst"),
                    'link' => 'https://wordpress.org/plugins/zip-recipes/',
                    'class' => 'zip',
                    'constant_free' => 'ZRDN_PLUGIN_BASENAME',
                    'constant_premium' => 'ZRDN_PREMIUM',
                    'website' => 'https://ziprecipes.net/premium/',
                    'search' => 'zip+recipes+recipe+maker+really+simple+plugins',                ),
            );

            $element = $this->get_template('dashboard/upsell-element.php');
            error_log(print_r($element, true));
            $output = '';
            foreach ($items as $item) {
            	error_log('item');
            	error_log(print_r($item, true));
                $output .= str_replace(array(
                    '{title}',
                    '{link}',
                    '{content}',
                    '{status}',
                    '{class}',
                ), array(
                    $item['title'],
                    $item['link'],
                    $item['content'],
                    $this->get_status_link($item),
                    $item['class'],
                    '',
                ), $element);
            }
            error_log('output');
            error_log(print_r($output, true));

            return '<div>'.$output.'</div>';
        }

        public function generate_tips_tricks()
        {
            $items = array(
                1 => array(
                    'content' => __("Writing Content for Google", "burst"),
                    'link'    => 'https://wpsearchinsights.com/writing-content-for-google/',
                ),
                2 => array(
                    'content' => __("WP Search Insights Beginner's Guide", "burst"),
                    'link' => 'https://wpsearchinsights.com/burst-beginners-guide/',
                ),
                3 => array(
                    'content' => __("Using CSV/Excel Exports", "burst"),
                    'link' => 'https://wpsearchinsights.com/using-csv-excel-exports/',
                ),
                4 => array(
                    'content' => __("Improving your Search Result Page", "burst"),
                    'link' => 'https://wpsearchinsights.com/improving-your-search-result-page/',
                ),
                5 => array(
                    'content' => __("The Search Filter", "burst"),
                    'link' => 'https://wpsearchinsights.com/the-search-filter/',
                ),
                6 => array(
                    'content' => __("Positioning your search form", "burst"),
                    'link' => 'https://wpsearchinsights.com/about-search-forms/',
                ),
            );
	        $button = '<a href="https://wpsearchinsights.com/tips-tricks/" target="_blank"><button class="button button-upsell">'.__("View all" , "burst").'</button></a>';

	        $container = $this->get_template('dashboard/tipstricks.php');
	        $output = "";
            foreach ($items as $item) {
	            $output .= str_replace(array(
                    '{link}',
                    '{content}',
                ), array(
                    $item['link'],
                    $item['content'],
                ), $container);
            }
            return '<div>'.$output.'</div>'.$button;
        }


		public function settings() {
			ob_start();
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

			$html = ob_get_clean();
			
			$args = array(
				'page' => 'general-settings',
				'content' => burst_grid_container($html),
			);
			echo burst_get_template('admin_wrap.php', $args );
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

        public function show_message() {
			if ( ! empty( $this->error_message ) ) {
				burst_notice( $this->error_message, 'warning' );
				$this->error_message = "";
			}

			if ( ! empty( $this->success_message ) ) {
				burst_notice( $this->success_message, 'success', true );
				$this->success_message = "";
			}
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
		        $status = __("Installed", "burst");
	        } elseif (defined($item['constant_free']) && !defined($item['constant_premium'])) {
		        $link = $item['website'];
		        $text = __('Upgrade to pro', 'burst');
		        $status = "<a href=$link>$text</a>";
	        } else {
		        $link = $install_url.$item['search']."&tab=search&type=term";
		        $text = __('Install', 'burst');
		        $status = "<a href=$link>$text</a>";
	        }
	        return $status;
        }

        public function hide_wordpress_and_other_plugin_notices(){
        	/**
        	* @todo Mag dit? Geen notices laten zien op onze pagina's. 
        	*/
        	if ( isset( $_GET['page'] ) && strpos($_GET['page'], 'burst') === 0 ) {
				if(! current_user_can('update_core')){ return; }
				add_filter('pre_option_update_core','__return_null');
				add_filter('pre_site_transient_update_core','__return_null');
				add_filter('pre_site_transient_update_plugins','__return_null');
				add_filter('pre_site_transient_update_themes','__return_null');
				add_filter('all_admin_notices','__return_null');
				add_filter('admin_notices','__return_null');
        	}

        }

        public function ajax_get_datatable()
	    {
		    $error = false;
		    $total = 0;
		    $html  = __("No data found", "burst");
		    if (!current_user_can('manage_options')) {
			    $error = true;
		    }

		    if (!isset($_GET['start'])){
			    $error = true;
		    }

		    if (!isset($_GET['end'])){
			    $error = true;
		    }

		    if (!isset($_GET['type'])){
			    $error = true;
		    }

		    if (!isset($_GET['token'])){
			    $error = true;
		    }

		    $page = isset($_GET['page']) ? intval($_GET['page']) : false;

		    if (!$error && !wp_verify_nonce(sanitize_title($_GET['token']), 'search_insights_nonce')){
			    $error = true;
		    }

		    if (!$error){
			    $start = intval($_GET['start']);
			    $end = intval($_GET['end']);
			    $type = sanitize_title($_GET['type']);
			    $total = $this->get_results_count($type, $start, $end);
			    switch ($type){
                    case 'all':
	                    $html = $this->recent_table( $start, $end, $page);
	                    break;
                    case 'popular':
	                    $html = $this->generate_dashboard_widget(true, $start, $end);
	                    break;
				    case 'results':
					    $html = $this->results_table( $start, $end);
					    break;
                    default:
                        $html = apply_filters("burst_ajax_content_$type", '');
                        break;
			    }
		    }

		    $data = array(
			    'success' => !$error,
			    'html' => $html,
                'total_rows' => $total,
                'batch' => $this->rows_batch,
		    );

		    $response = json_encode($data);
		    header("Content-Type: application/json");
		    echo $response;
		    exit;
	    }

	}
} //class closure
