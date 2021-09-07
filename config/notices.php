<?php
defined( 'ABSPATH' ) or die();
if ( ! class_exists( "burst_notices" ) ) {
	class burst_notices {
		private static $_this;

		public $notices;

		function __construct( $args = array() ) {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}
			add_action( 'wp_ajax_burst_dismiss_notice', array( $this, 'dismiss_notice' ) );

		}
		static function this() {
			return self::$_this;
		}

		/**
		 * Hooked into ajax call to dismiss a warning
		 * @hooked wp_ajax_burst_dismiss_warning
		 */

		public function dismiss_notice() {
			$error   = false;

			if ( ! is_user_logged_in() ) {
				$error = true;
			}

			if ( !isset($_POST['id']) ) {
				$error = true;
			}

			if ( !$error ) {
				$warning_id = sanitize_title($_POST['id']);
				$dismissed_warnings = get_option( 'burst_dismissed_warnings', array() );
				if ( !in_array($warning_id, $dismissed_warnings) ) {
					$dismissed_warnings[] = $warning_id;
				}
				update_option('burst_dismissed_warnings', $dismissed_warnings );
			}

			$out = array(
				'success' => ! $error,
			);

			die( json_encode( $out ) );
		}

		public function get_notices( $args = array() ) {
			$notices = apply_filters( 'burst_notices', array(
					'not_reached_sample_size' => array(
						'warning_condition' => 'burst_experiment_not_reached_sample_size',
						//				'success_conditions'  => array(
						//					'document->all_required_pages_created',
						//				),

						'open'     => __( 'An experiment has run one month, but has not reached the minimal sample size yet.', 'burst' ),
						'plus_one' => true,
					),
				)
			);

			$defaults = array(
				'cache'     => true,
				'status'    => 'all',
				'plus_ones' => false,
			);
			$args     = wp_parse_args( $args, $defaults );
			$cache    = $args['cache'];
			if ( isset( $_GET['page'] ) && ( $_GET['page'] === 'burst' ) ) {
				$cache = false;
			}

			$active_notices = $cache ? get_transient( 'burst_warnings' ) : false;
			//re-check if there are no warnings, or if the transient has expired
			if ( ! $active_notices ) {

				$warning_type_defaults = array(
					'plus_one'           => false,
					'warning_condition'  => '_true_',
					'success_conditions' => array(),
					'relation'           => 'OR',
					'status'             => 'open',
					'dismissable'        => true,
				);

				foreach ( $notices as $id => $warning_type ) {
					$notices[ $id ] = wp_parse_args( $warning_type, $warning_type_defaults );
				}

				$dismissed_warnings = get_option( 'burst_dismissed_warnings', array() );
				foreach ( $notices as $id => $warning ) {
					if ( in_array( $id, $dismissed_warnings ) ) {
						continue;
					}

					$show_warning = $this->validate_function( $warning['warning_condition'] );
					if ( ! $show_warning ) {
						continue;
					}

					$relation = $warning['relation'];
					if ( $relation === 'AND' ) {
						$success = true;
					} else {
						$success = false;
					}
					foreach ( $warning['success_conditions'] as $func ) {
						$condition = $this->validate_function( $func );
						if ( $relation === 'AND' ) {
							$success = $success && $condition;
						} else {
							$success = $success || $condition;
						}
					}

					if ( ! $success ) {
						if ( isset( $warning['open'] ) ) {
							$warning['message']    = $warning['open'];
							$warning['status']     = 'open';
							$active_notices[ $id ] = $warning;
						} else if ( isset( $warning['urgent'] ) ) {
							$warning['message']    = $warning['urgent'];
							$warning['status']     = 'urgent';
							$active_notices[ $id ] = $warning;
						}
					} else {
						if ( isset( $warning['completed'] ) ) {
							$warning['message']    = $warning['completed'];
							$warning['status']     = 'completed';
							$warning['plus_one']   = false;
							$active_notices[ $id ] = $warning;
						}
					}
				}
				set_transient( 'burst_warnings', $active_notices, HOUR_IN_SECONDS );
			}

			if (!is_array($active_notices)) $active_notices = array();

			//filtering outside cache if, to make sure all warnings are saved for the cache.
			//filter by status
			if ( $args['status'] !== 'all' ) {
				$filter_statuses = is_array( $args['status'] ) ? $args['status'] : array( $args['status'] );
				foreach ( $active_notices as $id => $warning ) {
					if ( ! in_array( $warning['status'], $filter_statuses ) ) {
						unset( $active_notices[ $id ] );
					}
				}
			}

			//filter by plus ones
			if ( $args['plus_ones'] ) {
				foreach ( $active_notices as $id => $warning ) {
					//prevent notices on upgrade to 5.0
					if ( ! isset( $warning['plus_one'] ) ) {
						continue;
					}

					if ( ! $warning['plus_one'] ) {
						unset( $active_notices[ $id ] );
					}
				}
			}

			//sort so warnings are on top
			$completed = array();
			$open      = array();
			$urgent    = array();
			foreach ( $active_notices as $key => $notice ) {
				//prevent notices on upgrade to 5.0
				if ( ! isset( $notice['status'] ) ) {
					continue;
				}

				if ( $notice['status'] === 'urgent' ) {
					$urgent[ $key ] = $notice;
				} else if ( $notice['status'] === 'open' ) {
					$open[ $key ] = $notice;
				} else {
					$completed[ $key ] = $notice;
				}
			}
			$active_notices = $urgent + $open + $completed;

			return $active_notices;
		}

		/**
		 * Render a notice
		 * @param $notice
		 */
		public function render_warning( $notice ) {
			$id     = key( $notice );
			$notice = $notice[ $id ];

			if ( $notice['status'] === 'completed' ) {
				$status_message = __( "Completed", 'burst' );
			}
			if ( $notice['status'] === 'open' ) {
				$status_message = __( "Open", 'burst' );
			}
			if ( $notice['status'] === 'urgent' ) {
				$status_message = __( "Urgent", 'burst' );
			}
			$plus_one = $notice['plus_one'] ? '<span class="burst-plusone">1</span>' : '';
			$dismiss  = '<button type="button" class="burst-dismiss-notice" data-notice_id="' . $id . '"><span class="burst-close-notice-x">X</span></button>';
			$args     = array(
				'status'         => $notice['status'],
				'status-message' => $status_message,
				'message'        => $notice['message'],
				'plus-one'       => $plus_one,
				'dismiss'        => $dismiss,
			);
			$template = burst_get_template( 'dashboard/notice.php', $args );
			echo $template;
		}


		/**
		 * Get output of function, in format 'function', or 'class()->sub()->function'
		 * We can pass one variable to the function
		 *
		 * @param string $func
		 *
		 * @return string|bool
		 */

		public function validate_function( $func ) {
			$invert = false;
			if ( strpos( $func, 'NOT ' ) !== false ) {
				$func   = str_replace( 'NOT ', '', $func );
				$invert = true;
			}

			if ( empty( $func ) ) {
				return true;
			}

			if ( strpos( $func, 'get_option_' ) !== false ) {
				$field  = str_replace( 'get_option_', '', $func );
				$output = get_option( $field );
			} else if ( $func === '_true_' ) {
				$output = true;
			} else if ( $func === '_false_' ) {
				$output = false;
			} else {
				//check if this is a function
				if ( function_exists( $func ) ) {
					$output = $func();
				}
				//check for object function array: array(obj, 'func')
				//untested yet.
				elseif ( is_array( $func ) ) {
					$output = $func[0]->$func[1]();
				}
			}

			if ( $invert ) {
				$output = ! $output;
			}

			return $output;
		}

	}
}
