<?php
/*100% match*/

defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "burst_wizard" ) ) {
	class burst_wizard {
		private static $_this;
		public $position;
		public $cookies = array();
		public $total_steps = false;
		public $last_section;
		public $page_url;
		public $percentage_complete = false;

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

			//callback from settings
			add_action( 'burst_wizard_last_step', array( $this, 'wizard_last_step_callback' ), 10, 1 );

			//link action to custom hook
			add_action( 'burst_wizard_experiment', array( $this, 'wizard_after_step' ), 10, 1 );

			//process custom hooks
			add_action( 'admin_init', array( $this, 'process_custom_hooks' ) );
			add_action( 'burst_before_save_wizard_option', array( $this, 'before_save_wizard_option' ), 10, 4 );
			add_action( 'burst_after_save_wizard_option', array( $this, 'after_save_wizard_option' ), 10, 4 );
			add_action( 'burst_after_saved_all_fields', array( $this, 'after_saved_all_fields' ), 10, 1 );

			//dataleaks:
			add_action( 'burst_is_wizard_completed', array( $this, 'is_wizard_completed_callback' ) );
		}

		static function this() {
			return self::$_this;
		}


		public function is_wizard_completed_callback() {
			if ( $this->wizard_completed_once() ) {
				burst_notice( __( "Great, the main wizard is completed. This means the general data is already in the system, and you can continue with the next question. This will start a new, empty document.",
						'burst' ) );
			} else {
				$link = '<a href="' . admin_url( 'admin.php?page=burst-wizard' )
				        . '">';
				burst_notice( sprintf( __( "The wizard isn't completed yet. If you have answered all required questions, you just need to click 'finish' to complete it. In the wizard some general data is entered which is needed for this document. %sPlease complete the wizard first%s.",
					'burst' ), $link, "</a>" ), 'warning' );
			}
		}

        // CONFLICT WITH COMPLIANZ WIZARD
		public function process_custom_hooks() {
			$wizard_type = ( isset( $_POST['wizard_type'] ) )
				? sanitize_title( $_POST['wizard_type'] ) : '';
			do_action( "burst_wizard_$wizard_type" );
		}

		/**
		 * Initialize a page in the wizard
		 * @param $page
		 */
		public function initialize( $page ) {
			$this->last_section = $this->last_section( $page, $this->step() );
			$this->page_url     = admin_url( 'admin.php?page=burst-' . $page );
			//if a post id was passed, we copy the contents of that page to the wizard settings.
			if ( isset( $_GET['experiment_id'] ) ) {
				$post_id = intval( $_GET['experiment_id'] );
				//get all fields for this page
				$fields = BURST::$config->fields( $page );
				foreach ( $fields as $fieldname => $field ) {
					$fieldvalue = get_post_meta( $post_id, $fieldname, true );
					if ( $fieldvalue ) {
						if ( ! BURST::$field->is_multiple_field( $fieldname ) ) {
							BURST::$field->save_field( $fieldname, $fieldvalue );
						} else {
							$field[ $fieldname ] = $fieldvalue;
							BURST::$field->save_multiple( $field );
						}
					}

				}
			}
		}

		/**
		 * Some actions after the last step has been completed
		 */
		public function wizard_last_step_callback() {;

			if ( ! $this->all_required_fields_completed( 'experiment' ) ) {
                echo '<div class="burst-wizard-intro">';
				_e( "Not all required fields are completed yet. Please check the steps to complete all required questions before you can start the experiment.", 'burst' );
                echo '</div>';
			} else {
			    $experiment = new BURST_EXPERIMENT($_GET['experiment_id']);
			    if ($experiment->status !== 'draft') {
			        echo '<div class="burst-wizard-intro"><p>' . __('Your experiment is already completed, active or archived.', 'burst') . '</p></div>';
                } else {
                    $args = array(
                        'title' => $experiment->title,
                        'goal' => $experiment->goal,
                        'significance' => $experiment->significance,

                        'control_title' => get_the_title($experiment->control_id),
                        'control_url' => str_replace( home_url(), "", get_permalink($experiment->control_id)),
                        'control_edit_url' => get_edit_post_link($experiment->control_id),

                        'variant_title' => get_the_title($experiment->variant_id),
                        'variant_url' => str_replace( home_url(), "", get_permalink($experiment->variant_id)),
                        'variant_edit_url' => get_edit_post_link($experiment->variant_id),

                    );
                    error_log(print_r($experiment, true));

                    echo burst_get_template('wizard/conclusion.php', $args);
                }
			}
		}


		/**
		 * Process completion of setup
		 *
		 * */

		public function wizard_after_step() {
			if ( ! burst_user_can_manage() ) {
				return;
			}

			//clear document cache

			//if the plugins page is reviewed, we can reset the privacy statement suggestions from WordPress.
//			if ( burst_wp_privacy_version()
//			     && ( $this->step( 'wizard' ) == STEP_MENU )
//			     && burst_get_value( 'privacy-statement' ) === 'generated'
//			) {
//				$policy_page_id = (int) get_option( 'wp_page_for_privacy_policy' );
//				WP_Privacy_Policy_Content::_policy_page_updated( $policy_page_id );
//				//check again, to update the cache.
//				WP_Privacy_Policy_Content::text_change_check();
//			}

			//BURST::$admin->reset_burst_plugin_has_new_features();

			//when clicking to the last page, or clicking finish, run the finish sequence.
//			if ( isset( $_POST['burst-finish'] )
//			     || ( isset( $_POST["step"] ) && $_POST['step'] == STEP_START
//			          && isset( $_POST['burst-next'] ) )
//			) {
//				$this->set_wizard_completed_once();
//			}
		}

		/**
		 * Do stuff before a page from the wizard is saved.
		 *
		 * */

		public function before_save_wizard_option(
			$fieldname, $fieldvalue, $prev_value, $type
		) {

			update_option( 'burst_documents_update_date', time() );

			//only run when changes have been made
			if ( $fieldvalue === $prev_value ) {
				return;
			}

		}

		/**
		 * Handle some custom options after saving the wizard options
		 *
		 * After all fields have been saved
		 * @param $posted_fields
		 */

		public function after_saved_all_fields($posted_fields){
			//if the region is not EU anymore, and it was previously enabled for EU / eu_consent_regions, reset impressum
			if ( array_key_exists('burst_regions', $posted_fields) && burst_get_value('eu_consent_regions') === 'yes' && !burst_has_region('eu')
			) {
				burst_update_option('wizard', 'eu_consent_regions', 'no' );
			}
		}

		/**
		 * Handle some custom options after saving the wizard options
		 * @param string $fieldname
		 * @param mixed $fieldvalue
		 * @param mixed $prev_value
		 * @param string $type
		 */

		public function after_save_wizard_option( $fieldname, $fieldvalue, $prev_value, $type ) {

		}

		/**
		 * Get the next step with fields in it
		 * @param string $page
		 * @param int $step
		 *
		 * @return int
		 */
		public function get_next_not_empty_step( $page, $step ) {
			if ( ! BURST::$field->step_has_fields( $page, $step ) ) {
				if ( $step >= $this->total_steps( $page ) ) {
					return $step;
				}
				$step ++;
				$step = $this->get_next_not_empty_step( $page, $step );
			}

			return $step;
		}

		/**
		 * Get the next section which is not empty
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return int|bool
		 */
		public function get_next_not_empty_section( $page, $step, $section ) {

			if ( ! BURST::$field->step_has_fields( $page, $step, $section ) ) {
				//some keys are missing, so we need to count the actual number of keys.
				if ( isset( BURST::$config->steps[ $page ][ $step ]['sections'] ) ) {
					$n = array_keys( BURST::$config->steps[ $page ][ $step ]['sections'] ); //<---- Grab all the keys of your actual array and put in another array
					$count = array_search( $section, $n ); //<--- Returns the position of the offset from this array using search

					//this is the actual list up to section key.
					$new_arr = array_slice( BURST::$config->steps[ $page ][ $step ]['sections'], 0, $count + 1, true );//<--- Slice it with the 0 index as start and position+1 as the length parameter.
					$section_count = count( $new_arr ) + 1;
				} else {
					$section_count = $section + 1;
				}
				$section ++;

				if ( $section_count > $this->total_sections( $page, $step ) ) {
					return false;
				}

				$section = $this->get_next_not_empty_section( $page, $step, $section );
			}

			return $section;
		}

		public function get_previous_not_empty_step( $page, $step ) {
			if ( ! BURST::$field->step_has_fields( $page, $step ) ) {
				if ( $step <= 1 ) {
					return $step;
				}
				$step --;
				$step = $this->get_previous_not_empty_step( $page, $step );
			}

			return $step;
		}

		public function get_previous_not_empty_section( $page, $step, $section
		) {

			if ( ! BURST::$field->step_has_fields( $page, $step,
				$section )
			) {
				$section --;
				if ( $section < 1 ) {
					return false;
				}
				$section = $this->get_previous_not_empty_section( $page, $step,
					$section );
			}

			return $section;
		}

		/*
		 * Lock the wizard for further use while it's being edited by the current user.
		 *
		 *
		 * */

		public function lock_wizard() {
			$user_id = get_current_user_id();
			set_transient( 'burst_wizard_locked_by_user', $user_id,
				apply_filters( "burst_wizard_lock_time",
					2 * MINUTE_IN_SECONDS ) );
		}


		/*
		 * Check if the wizard is locked by another user
		 *
		 *
		 * */

		public function wizard_is_locked() {
			$user_id      = get_current_user_id();
			$lock_user_id = $this->get_lock_user();
			if ( $lock_user_id && $lock_user_id != $user_id ) {
				return true;
			}

			return false;
		}

		public function get_lock_user() {
			return get_transient( 'burst_wizard_locked_by_user' );
		}


		public function wizard( $page, $wizard_title = '' )
        {

            if (!burst_user_can_manage()) {
                return;
            }

            if ($this->wizard_is_locked()) {
                $user_id = $this->get_lock_user();
                $user = get_user_by("id", $user_id);
                $lock_time = apply_filters("burst_wizard_lock_time",
                        2 * MINUTE_IN_SECONDS) / 60;

                burst_notice(sprintf(__("The wizard is currently being edited by %s",
                        'burst'), $user->user_nicename) . '<br>'
                    . sprintf(__("If this user stops editing, the lock will expire after %s minutes.",
                        'burst'), $lock_time), 'warning');

                return;
            }
            //lock the wizard for other users.
            $this->lock_wizard();


            $this->initialize($page);

            $section = $this->section();
            $step = $this->step();
            error_log('step: ');
            error_log($step);

            error_log('section: ');
            error_log($section);

            error_log('section is empty');
            error_log($this->section_is_empty($page, $step, $section));




            if ($this->section_is_empty($page, $step, $section)
                || (isset($_POST['burst-next'])
                    && !BURST::$field->has_errors())
            ) {
                error_log('no errors');
                if (BURST::$config->has_sections($page, $step)
                    && ($section < $this->last_section)
                ) {
                    $section = $section + 1;
                } else {
                    $step++;
                    $section = $this->first_section($page, $step);
                }

                $step = $this->get_next_not_empty_step($page, $step);
                $section = $this->get_next_not_empty_section($page, $step,
                    $section);
                //if the last section is also empty, it will return false, so we need to skip the step too.
                if (!$section) {
                    $step = $this->get_next_not_empty_step($page,
                        $step + 1);
                    $section = 1;
                }
            }

            if (isset($_POST['burst-previous'])) {
                if (BURST::$config->has_sections($page, $step)
                    && $section > $this->first_section($page, $step)
                ) {
                    $section--;
                } else {
                    $step--;
                    $section = $this->last_section($page, $step);
                }

                $step = $this->get_previous_not_empty_step($page, $step);
                $section = $this->get_previous_not_empty_section($page, $step,
                    $section);
            }

            $menu = $this->wizard_menu( $page, $wizard_title, $step, $section );
            $content = $this->wizard_content($page, $step, $section );

            $args = array(
                'page' => 'wizard',
                'content' => $menu.$content,
            );
            echo burst_get_template('admin_wrap.php', $args );
        }

		/**
		 * Generate menu
		 * @param string $page
		 * @param string $wizard_title
		 * @param int $active_step
		 * @param int $active_section
		 *
		 * @return false|string
		 */
		public function wizard_menu( $page, $wizard_title, $active_step, $active_section )
        {
            $args_menu['steps'] = "";
            for ($i = 1; $i <= $this->total_steps($page); $i++)
            {
                $args['title'] = $i . '. ' . BURST::$config->steps[$page][$i]['title'];
                $args['active'] = ($i == $active_step) ? 'active' : '';
                $args['completed'] = $this->required_fields_completed($page, $i, false) ? 'complete' : 'incomplete';
                $args['url'] = add_query_arg(array('step' => $i), $this->page_url);
                if ($this->experiment_id())
                {
                    $args['url'] = add_query_arg(array('experiment_id' => $this->experiment_id()), $args['url']);
                }
                $args['sections'] = ($args['active'] == 'active') ? $this->wizard_sections($page, $active_step, $active_section) : '';

                $args_menu['steps'] .= burst_get_template( 'wizard/step.php' , $args);
            }

            $args_menu['title'] = !empty( $wizard_title ) ? $wizard_title : __( "The Wizard", 'burst' );

            return burst_get_template( 'wizard/menu.php', $args_menu );
        }

		/**
		 * @param string $page
		 * @param int $step
		 * @param int $active_section
		 *
		 * @return string
		 */
        public function wizard_sections( $page, $step, $active_section ) {
            $sections = "";

	        if ( BURST::$config->has_sections( $page, $step )) {

		        for ($i = $this->first_section( $page, $step ); $i <= $this->last_section( $page, $step ); $i ++) {
			        $icon = burst_icon('check', 'empty', '' , 10);

			        if ( $this->section_is_empty( $page, $step, $i ) ) continue;
                    if ( $i < $this->get_next_not_empty_section( $page, $step, $i ) ) continue;

                    $active = ( $i == $active_section ) ? 'active' : '';
                    if ( $active == 'active' ) {
                        $icon = burst_icon('arrow-right', 'success', '', 10);
                    } else if ($this->required_fields_completed( $page, $step, $i )) {
                    	$icon = burst_icon('check', 'success', '', 12);
                    }

                    $completed = ( $this->required_fields_completed( $page, $step, $i ) ) ? "burst-done" : "burst-to-do";
                    $url = add_query_arg( array('step' => $step, 'section' => $i), $this->page_url );
                    if ( $this->experiment_id() ) {
                        $url = add_query_arg( array( 'experiment_id' => $this->experiment_id() ), $url );
                    }

                    $title = BURST::$config->steps[ $page ][ $step ]['sections'][ $i ]['title'];
                    $args = array(
	                    'active' => $active,
	                    'completed' => $completed,
	                    'icon' => $icon,
	                    'url' => $url,
	                    'title' => $title,
                    );
	                $sections .= burst_get_template( 'wizard/section.php', $args );
                }
            }

            return $sections;
        }

		public function wizard_content( $page, $step, $section ) {
			$args = array(
				'title' => '',
				'page' => $page,
				'step' => $step,
				'section' => $section,
				'previous_button' => '',
				'next_button' => '',
				'save_button' => '',
				'intro' => $this->get_intro( $page, $step, $section ),
				'page_url' => $this->page_url,
				'post_id' => $this->experiment_id() ? '<input type="hidden" value="' . $this->experiment_id() . '" name="experiment_id">' : '',
			);
            if ( isset(BURST::$config->steps[$page][$step]['sections'][$section]['title'])) {
                $args['title'] = BURST::$config->steps[$page][$step]['sections'][$section]['title'];
            } else {
                $args['title'] .= BURST::$config->steps[$page][$step]['title'];
            }


            if ( $page != 'wizard' ) {
                if ( $step == 1 ) {
                    delete_option( 'burst_options_' . $page );

                }
            }

            ob_start();
            BURST::$field->get_fields( $page, $step, $section );
            $args['fields'] = ob_get_clean();

            if ( $step > 1 || $section > 1 ) {
                $args['previous_button'] = '<input class="button button-link burst-previous" type="submit" name="burst-previous" value="'. __( "Previous", 'burst' ) . '">';
            }

            if ( $step < $this->total_steps( $page ) ) {
                $args['next_button'] = '<input class="button button-primary burst-next" type="submit" name="burst-next" value="'. __( "Save and continue", 'burst' ) . '">';
            } else if ( $step == $this->total_steps( $page ) && $this->all_required_fields_completed( $page )) {
                $args['next_button'] = '<input class="button button-primary burst-finish" type="submit" name="burst-finish" value="'. __('Start experiment', 'burst') . '">';
            }

            if ( $page == 'experiment' )  {
                    $args['save_button'] = '<input class="button button-secondary burst-save" type="submit" name="burst-save" value="'. __( "Save", 'burst' ) . '">';
            }
            if ( $step < $this->total_steps( $page ) ) {
                $args['next_button'] = '<input class="button button-primary burst-next" type="submit" name="burst-next" value="'. __( "Save and continue", 'burst' ) . '">';
            } else if ( $step == $this->total_steps( $page ) && $this->all_required_fields_completed( $page )) {
                $args['next_button'] = '<input class="button button-primary burst-finish center" type="submit" name="burst-finish" value="'. __('Start experiment', 'burst') . '">';
                $args['save_button'] = '';
                $args['previous_button'] = '';
            }

            return burst_get_template( 'wizard/content.php', $args );
        }

		/**
		 * If a section does not contain any fields to be filled, just drop it from the menu.
		 * @return bool
		 *
		 * */

		public function section_is_empty( $page, $step, $section ) {
			$section_compare = $this->get_next_not_empty_section( $page, $step, $section );
			if ( $section != $section_compare ) {
				return true;
			}

			return false;
		}

		/**
		 * Enqueue assets
		 * @param $hook
		 */
		public function enqueue_assets( $hook ) {

			$minified = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';


			if ( isset($_GET['page']) && $_GET['page'] !== 'burst-experiment' ) {
			    return;
            }

			//also skip the wizard for root pages of dataleaks and processing
			if ( strpos( $hook, 'post_type' ) !== false ) {
				return;
			}
			wp_register_style( 'burst-wizard', burst_url . "assets/css/wizard$minified.css", false, burst_version );
			wp_enqueue_style( 'burst-wizard' );
		}


		/**
		 * Foreach required field, check if it's been answered
		 * if section is false, check all fields of the step.
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return bool
		 */


		public function required_fields_completed( $page, $step, $section ) {
			//get all required fields for this section, and check if they're filled in
			$fields = BURST::$config->fields( $page, $step, $section );

			//get
			$fields = burst_array_filter_multidimensional( $fields, 'required', true );
			foreach ( $fields as $fieldname => $args ) {
				//if a condition exists, only check for this field if the condition applies.
				if ( isset( $args['condition'] )
				     || isset( $args['callback_condition'] )
				        && ! BURST::$field->condition_applies( $args )
				) {
					continue;
				}
				$value = BURST::$field->get_value( $fieldname );
				if ( empty( $value ) ) {
					return false;
				}
			}
			return true;
		}

		public function all_required_fields_completed_wizard(){
			return $this->all_required_fields_completed('wizard');
		}

		/**
		 * Check if all required fields are filled
		 * @return bool
		 *
		 * */

		public function all_required_fields_completed( $page ) {
			for ( $step = 1; $step <= $this->total_steps( $page ); $step ++ ) {
				if ( BURST::$config->has_sections( $page, $step ) ) {
					for (
						$section = $this->first_section( $page, $step );
						$section <= $this->last_section( $page, $step );
						$section ++
					) {
						if ( ! $this->required_fields_completed( $page, $step,
							$section )
						) {
							return false;
						}
					}
				} else {
					if ( ! $this->required_fields_completed( $page, $step,
						false )
					) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 *
		 * Get the current selected post id for experiments
		 * @return int
		 *
		 * */

		public function post_id() {
			$post_id = false;
			if ( isset( $_GET['post_id'] ) || isset( $_POST['post_id'] ) ) {
				$post_id = ( isset( $_GET['post_id'] ) )
					? intval( $_GET['post_id'] ) : intval( $_POST['post_id'] );
			}

			return $post_id;
		}

        /**
         *
         * Get the current selected post id for experiments
         * @return int
         *
         * */

        public function experiment_id() {
            $post_id = false;
            if ( isset( $_GET['experiment_id'] ) || isset( $_POST['experiment_id'] ) ) {
                $post_id = ( isset( $_GET['experiment_id'] ) )
                    ? intval( $_GET['experiment_id'] ) : intval( $_POST['experiment_id'] );
            }

            return $post_id;
        }

		/**
		 * Get selected wizard type
		 * @return string
		 */
		public function wizard_type() {
			$wizard_type = 'wizard';
			if ( isset( $_POST['wizard_type'] )
			     || isset( $_POST['wizard_type'] )
			) {
				$wizard_type = isset( $_POST['wizard_type'] )
					? $_POST['wizard_type'] : $_GET['wizard_type'];
			} else {
				if ( isset( $_GET['page'] ) ) {
					$wizard_type = str_replace( 'burst-', '', $_GET['page'] );
				}
			}

			return $wizard_type;
		}


		/**
		 * Get a notice style header with an intro above a step or section
		 *
		 * @param string $page
		 * @param int $step
		 * @param int $section
		 *
		 * @return string
		 */

		public function get_intro( $page, $step, $section ) {
			//only show when in action
			$intro = '';
			if ( BURST::$config->has_sections( $page, $step ) ) {
				if ( isset( BURST::$config->steps[ $page ][ $step ]['sections'][ $section ]['intro'] ) ) {
					$intro .= BURST::$config->steps[ $page ][ $step ]['sections'][ $section ]['intro'];
				}
			} else {
				if ( isset( BURST::$config->steps[ $page ][ $step ]['intro'] ) ) {
					$intro .= BURST::$config->steps[ $page ][ $step ]['intro'];
				}
			}

			if ( strlen( $intro ) > 0 ) {
				$intro = '<div class="burst-wizard-intro">'
				         . $intro
				         . '</div>';
			}

			return $intro;
		}


		/**
		 * Retrieves the region to which this step applies
		 *
		 * @param $page
		 * @param $step
		 * @param $section
		 *
		 * @return array|bool
		 */
		public function get_section_regions( $page, $step, $section ) {
			//only show when in action
			$regions = array();

			if ( BURST::$config->has_sections( $page, $step ) ) {
				if ( isset( BURST::$config->steps[ $page ][ $step ]['sections'][ $section ]['region'] ) ) {
					$regions = BURST::$config->steps[ $page ][ $step ]['sections'][ $section ]['region'];
				}
			} else {
				if ( isset( BURST::$config->steps[ $page ][ $step ]['region'] ) ) {
					$regions = BURST::$config->steps[ $page ][ $step ]['region'];
				}
			}

			if ( $regions ) {
				if ( ! is_array( $regions ) ) {
					$regions = array( $regions );
				}

				foreach ( $regions as $index => $region ) {
					if ( ! burst_has_region( $region ) ) {
						unset( $regions[ $index ] );
					}
				}

			}
			if ( $regions ) {
				$regions = array_map( 'strtoupper', $regions );
			}
			return $regions;
		}


		public function get_type( $post_id = false ) {
			$page = false;
			if ( $post_id ) {
				$region    = BURST::$document->get_region( $post_id );
				$post_type = get_post_type( $post_id );
				$page      = str_replace( 'burst-', '', $post_type ) . '-'
				             . $region;
			}
			if ( isset( $_GET['page'] ) ) {
				$page = str_replace( 'burst-', '',
					sanitize_title( $_GET['page'] ) );
			}

			return $page;
		}


		public function wizard_completed_once() {
			return get_option( 'burst_wizard_completed_once' );
		}

		public function step( $page = false ) {
			$step = 1;
			if ( ! $page ) {
				$page = $this->wizard_type();
			}

			$total_steps = $this->total_steps( $page );

			if ( isset( $_GET["step"] ) ) {
				$step = intval( $_GET['step'] );
			}

			if ( isset( $_POST["step"] ) ) {
				$step = intval( $_POST['step'] );
			}

			if ( $step > $total_steps ) {
				$step = $total_steps;
			}

			if ( $step <= 1 ) {
				$step = 1;
			}

			return $step;
		}

		public function section() {
			$section = 1;
			if ( isset( $_GET["section"] ) ) {
				$section = intval( $_GET['section'] );
			}

			if ( isset( $_POST["section"] ) ) {
				$section = intval( $_POST['section'] );
			}

			if ( $section > $this->last_section ) {
				$section = $this->last_section;
			}

			if ( $section <= 1 ) {
				$section = 1;
			}

			return $section;
		}

		/**
		 * Get total number of steps for a page
		 *
		 * @param $page
		 *
		 * @return int
		 */

		public function total_steps( $page ) {
			return count( BURST::$config->steps[ $page ] );
		}

		public function total_sections( $page, $step ) {
			if ( ! isset( BURST::$config->steps[ $page ][ $step ]['sections'] ) ) {
				return 0;
			}

			return count( BURST::$config->steps[ $page ][ $step ]['sections'] );
		}


		public function last_section( $page, $step ) {
			if ( ! isset( BURST::$config->steps[ $page ][ $step ]["sections"] ) ) {
				return 1;
			}

			$array = BURST::$config->steps[ $page ][ $step ]["sections"];

			return max( array_keys( $array ) );

		}

		public function first_section( $page, $step ) {
			if ( ! isset( BURST::$config->steps[ $page ][ $step ]["sections"] ) ) {
				return 1;
			}

			$arr       = BURST::$config->steps[ $page ][ $step ]["sections"];
			$first_key = key( $arr );

			return $first_key;
		}

	}

} //class closure
