<?php
/*100% match*/

defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "burst_field" ) ) {
	class burst_field {
		private static $_this;
		public $position;
		public $fields;
		public $default_args;
		public $form_errors = array();

		function __construct() {
			if ( isset( self::$_this ) ) {
				wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
					get_class( $this ) ) );
			}

			self::$_this = $this;

			add_action( 'plugins_loaded', array( $this, 'process_save' ), 20 );
			add_action( 'burst_before_label', array( $this, 'before_label' ), 10, 1 );
			add_action( 'burst_before_label', array( $this, 'show_errors' ), 10, 1 );
			add_action( 'burst_after_label', array( $this, 'after_label' ), 10, 1 );
			add_action( 'burst_after_field', array( $this, 'after_field' ), 10, 1 );
			$this->load();
		}

		static function this() {
			return self::$_this;
		}


		public function load() {
			$this->default_args = array(
				"fieldname"          => '',
				"type"               => 'text',
				"required"           => false,
				'default'            => '',
				'label'              => '',
				'table'              => false,
				'callback_condition' => false,
				'condition'          => false,
				'callback'           => false,
				'placeholder'        => '',
				'optional'           => false,
				'disabled'           => false,
				'hidden'             => false,
				'region'             => false,
				'media'              => true,
				'first'              => false,
				'warn'               => false,
				'cols'               => false,
			);
		}

		public function process_save() {

			if ( ! burst_user_can_manage() ) {
				return;
			}

            if ( isset( $_POST['burst_nonce'] ) ) {
                //check nonce
                if ( ! isset( $_POST['burst_nonce'] )
                    || ! wp_verify_nonce( $_POST['burst_nonce'],
                        'burst_save' )
                ) {
                    return;
                }

                //save data
                $posted_fields = array_filter( $_POST, array( $this, 'filter_burst_fields' ), ARRAY_FILTER_USE_KEY );
                foreach ( $posted_fields as $fieldname => $fieldvalue ) {
                    $this->save_field( $fieldname, $fieldvalue );
                }
                do_action('burst_after_saved_all_fields', $posted_fields );
            }
        }


		/**
		 * Check if this is a conditional field
		 *
		 * @param $fieldname
		 *
		 * @return bool
		 */

		public function is_conditional( $fieldname ) {
			$fields = BURST::$config->fields();
			if ( isset( $fields[ $fieldname ]['condition'] )
			     && $fields[ $fieldname ]['condition']
			) {
				return true;
			}

			return false;
		}

		public function save_field( $fieldname, $fieldvalue ) {
			if ( ! burst_user_can_manage() ) {
				return;
			}

			$fieldvalue = apply_filters("burst_fieldvalue", $fieldvalue, $fieldname);

			$fields    = BURST::$config->fields();
			$fieldname = str_replace( "burst_", '', $fieldname );

			//do not save callback fields
			if ( isset( $fields[ $fieldname ]['callback'] ) ) {
				return;
			}

			$type     = $fields[ $fieldname ]['type'];
			$source     = $fields[ $fieldname ]['source'];

			//only save settings of type settings_general
			if ($source !== 'settings') return;

			$required = isset( $fields[ $fieldname ]['required'] ) ? $fields[ $fieldname ]['required'] : false;

			$fieldvalue = $this->sanitize( $fieldvalue, $type );
			if ( ! $this->is_conditional( $fieldname ) && $required
			     && empty( $fieldvalue )
			) {
				$this->form_errors[] = $fieldname;
			}


			$options = get_option( 'burst_options_' . $source );
			if ( ! is_array( $options ) ) {
				$options = array();
			}
			$prev_value = isset( $options[ $fieldname ] ) ? $options[ $fieldname ] : false;
			do_action( "burst_before_save_" . $source . "_option", $fieldname, $fieldvalue, $prev_value, $type );
			$options[ $fieldname ] = $fieldvalue;

			if ( ! empty( $options ) ) {
				update_option( 'burst_options_' . $source, $options );
			}

			do_action( "burst_after_save_" . $source . "_option", $fieldname, $fieldvalue, $prev_value, $type );
		}

		public function sanitize( $value, $type ) {
			if ( ! burst_user_can_manage() ) {
				return false;
			}

			switch ( $type ) {
				case 'colorpicker':
					return sanitize_hex_color( $value );
				case 'multicheckbox':
					if ( ! is_array( $value ) ) {
						$value = array( $value );
					}
					return array_map( 'sanitize_text_field', $value );
				case 'text':
				case 'phone':
					return sanitize_text_field( $value );
                case 'email':
					return sanitize_email( $value );
				case 'url':
					return esc_url_raw( $value );
				case 'number':
				case 'weightslider':
				    return intval( $value );
				case 'css':
				case 'javascript':
					return  $value ;
				case 'editor':
				case 'textarea':
					return wp_kses_post( $value );
			}

			return sanitize_text_field( $value );
		}

		/**/

		private
		function filter_burst_fields(
			$fieldname
		) {
			if ( strpos( $fieldname, 'burst_' ) !== false
			     && isset( BURST::$config->fields[ str_replace( 'burst_',
						'', $fieldname ) ] )
			) {
				return true;
			}

			return false;
		}

		public
		function before_label(
			$args
		) {

            $condition_class    = '';
            $condition_question = '';
            $condition_answer   = '';

            if ( ! empty( $args['condition'] ) ) {
                $condition_count    = 1;
                foreach ( $args['condition'] as $question => $answer ) {
                    $question = esc_attr( $question );
                    $answer = esc_attr( $answer );
                    $condition_class     .= "condition-check-{$condition_count} ";
                    $condition_question  .= "data-condition-answer-{$condition_count}='{$answer}' ";
                    $condition_answer    .= "data-condition-question-{$condition_count}='{$question}' ";
                    $condition_count++;
                }
            }

            $hidden_class    = ( $args['hidden'] ) ? 'hidden' : '';
            $burst_hidden    = $this->condition_applies( $args ) ? '' : 'burst-hidden';
            $first_class     = ( $args['first'] ) ? 'first' : '';
            $type            = $args['type'];

            $cols_class      = isset($args['cols']) && $args['cols']  ? "burst-cols-{$args['cols']}" : '';
            $col_class       = isset($args['col'])                    ? "burst-col-{$args['col']}" : '';
            $colspan_class   = isset($args['colspan'])                ? "burst-colspan-{$args['colspan']}" : '';

            $this->get_master_label( $args, $hidden_class . ' ' .
                $first_class . ' ' .
                $condition_class . ' ' .
                $burst_hidden );

            echo '<div class="field-group ' .
                esc_attr( $args['fieldname'] . ' ' .
                    esc_attr( $cols_class ) . ' ' .
                    esc_attr( $col_class ) . ' ' .
                    esc_attr( $colspan_class ) . ' ' .
                    'burst-'. $type . ' ' .
                    $hidden_class . ' ' .
                    $first_class . ' ' .
                    $condition_class . ' ' .
                    $burst_hidden )
                . '" ';

            echo $condition_question;
            echo $condition_answer;

            echo '><div class="burst-label">';
			
		}

		public function get_master_label( $args ) {
			if ( ! isset( $args['master_label'] ) ) {
				return;
			}
			?>
			<div
				class="burst-master-label"><?php echo esc_html( $args['master_label'] ) ?></div>
			<hr>
			<?php

		}

		public
		function show_errors(
			$args
		) {
			if ( in_array( $args['fieldname'], $this->form_errors ) ) {
				?>
				<div class="burst-form-errors">
					<?php _e( "This field is required. Please complete the question before continuing",
						'burst' ) ?>
				</div>
				<?php
			}
		}

		public
		function after_label(
			$args
		) {

			echo '</div><div class="burst-field">';
			

			do_action( 'burst_notice_' . $args['fieldname'], $args );

		}

        public function after_field( $args ) {

            $this->get_comment( $args );
            echo '</div><!--close after field-->';
            echo '<div class="burst-help-warning-wrap">';
            if (  isset( $args['help'] ) ) {
                $status = isset($args['help_status']) ? $args['help_status'] : 'notice';
                burst_sidebar_notice( wp_kses_post( $args['help'] ), $status, $args['condition'] );
            }

            do_action( 'burst_notice_' . $args['fieldname'], $args );

            echo '</div>';
            echo '</div>';
        }

		public
		function text(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<input <?php if ( $args['required'] ) {
				echo 'required';
			} ?>
				class="validation <?php if ( $args['required'] ) {
					echo 'is-required';
				} ?>"
				placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
				type="text"
				value="<?php echo esc_html( $value ) ?>"
				name="<?php echo esc_html( $fieldname ) ?>">
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function url(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'],
				$args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<input <?php if ( $args['required'] ) {
				echo 'required';
			} ?>
				class="validation <?php if ( $args['required'] ) {
					echo 'is-required';
				} ?>"
				placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
				type="text"
				pattern="^(http(s)?(:\/\/))?(www\.)?[#a-zA-Z0-9-_\.\/\:]+"
				value="<?php echo esc_html( $value ) ?>"
				name="<?php echo esc_html( $fieldname ) ?>">
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function email(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'],
				$args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<input <?php if ( $args['required'] ) {
				echo 'required';
			} ?>
				class="validation <?php if ( $args['required'] ) {
					echo 'is-required';
				} ?>"
				placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
				type="email"
				value="<?php echo esc_html( $value ) ?>"
				name="<?php echo esc_html( $fieldname ) ?>">
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function phone(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'],
				$args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<input autocomplete="tel" <?php if ( $args['required'] ) {
				echo 'required';
			} ?>
			       class="validation <?php if ( $args['required'] ) {
				       echo 'is-required';
			       } ?>"
			       placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
			       type="text"
			       value="<?php echo esc_html( $value ) ?>"
			       name="<?php echo esc_html( $fieldname ) ?>">
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function number(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'],
				$args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<input <?php if ( $args['required'] ) {
				echo 'required';
			} ?>
				class="validation <?php if ( $args['required'] ) {
					echo 'is-required';
				} ?>"
				placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
				type="number"
				value="<?php echo esc_html( $value ) ?>"
				name="<?php echo esc_html( $fieldname ) ?>"
				min="<?php echo isset($args["minimum"]) ? intval($args["minimum"]) : 0?>" step="<?php echo isset($args["validation_step"]) ? intval($args["validation_step"]) : 1?>"
				>
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}


		public
		function checkbox(
			$args, $force_value = false
		) {
			$fieldname = 'burst_' . $args['fieldname'];

			$value             = $force_value ? $force_value
				: $this->get_value( $args['fieldname'], $args['default'] );
			$placeholder_value = ( $args['disabled'] && $value ) ? $value : 0;
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>
			<?php do_action( 'burst_before_label', $args ); ?>

			<label class="<?php if ( $args['disabled'] ) {
				echo 'burst-disabled';
			} ?>"
			       for="<?php echo esc_html( $fieldname ) ?>-label"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>

			<?php do_action( 'burst_after_label', $args ); ?>

			<label class="burst-switch">
				<input name="<?php echo esc_html( $fieldname ) ?>" type="hidden"
				       value="<?php echo $placeholder_value ?>"/>

				<input name="<?php echo esc_html( $fieldname ) ?>" size="40"
				       type="checkbox"
					<?php if ( $args['disabled'] ) {
						echo 'disabled';
					} ?>
					   class="<?php if ( $args['required'] ) {
						   echo 'is-required';
					   } ?>"
					   value="1" <?php checked( 1, $value, true ) ?> />
				<span class="burst-slider burst-round"></span>
			</label>

			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function multicheckbox(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'] );
			if ( ! is_array( $value ) ) {
				$value = array();
			}

			//if no value at all has been set, assign a default value
			$has_selection = false;
			foreach ( $value as $key => $index ) {
				if ( $index == 1 ) {
					$has_selection = true;
					break;
				}
			}

			$default_index = $args['default'];

			if ( ! $this->show_field( $args ) ) {
				return;
			}

			?>
			<?php do_action( 'burst_before_label', $args ); ?>

			<label
				for="<?php echo esc_html( $fieldname ) ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>

			<?php do_action( 'burst_after_label', $args ); ?>
			<?php if ( ! empty( $args['options'] ) ) { ?>
				<div class="<?php if ( $args['required'] )
					echo 'burst-validate-multicheckbox' ?>">
					<?php foreach (
						$args['options'] as $option_key => $option_label
					) {
						$sel_key = false;
						if ( ! $has_selection ) {
							$sel_key = $default_index;
						} elseif ( isset( $value[ $option_key ] )
						           && $value[ $option_key ]
						) {
							$sel_key = $option_key;
						}
						?>
						<div>
							<input
								name="<?php echo esc_html( $fieldname ) ?>[<?php echo $option_key ?>]"
								type="hidden" value=""/>
							<input class="<?php if ( $args['required'] ) {
								echo 'is-required';
							} ?>"
							       name="<?php echo esc_html( $fieldname ) ?>[<?php echo $option_key ?>]"
							       size="40" type="checkbox"
							       value="1" <?php echo ( (string) ( $sel_key
							                                         == (string) $option_key ) )
								? "checked" : "" ?> >
							<label>
								<?php echo esc_html( $option_label ) ?>
							</label>
						</div>
					<?php } ?>
				</div>
			<?php } else {
				burst_notice( __( 'No options found', 'burst' ) );
			} ?>

			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function radio(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'],
				$args['default'] );
			$options   = $args['options'];

			if ( ! $this->show_field( $args ) ) {
				return;
			}

			?>
			<?php do_action( 'burst_before_label', $args ); ?>

			<label class="label"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>

			<?php do_action( 'burst_after_label', $args ); ?>
			<div class="burst-validate-radio">
				<?php
				if ( ! empty( $options ) ) {
					if ( $args['disabled'] ) {
						echo '<input type="hidden" value="' . $args['default']
						     . '" name="' . $fieldname . '">';
					}
					foreach ( $options as $option_value => $option_label ) {
						?>
						<label for="<?php echo esc_html( $option_value ) ?>" class="">
						<input <?php if ( $args['disabled'] )
							echo "disabled" ?>
							<?php if ( $args['required'] ) {
								echo "required";
							} ?>
							type="radio"
							id="<?php echo esc_html( $option_value ) ?>"
							name="<?php echo esc_html( $fieldname ) ?>"
							value="<?php echo esc_html( $option_value ); ?>" <?php if ( $value
							                                                            == $option_value
						)
							echo "checked" ?>>
						
							<?php echo esc_html( $option_label ); ?>
						</label>
						<div class="clear"></div>
					<?php }
				}
				?>
			</div>

			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}


		public
		function show_field(
			$args
		) {
			$show = ( $this->condition_applies( $args, 'callback_condition' ) );

			return $show;
		}


		public function function_callback_applies( $func ) {
			$invert = false;

			if ( strpos( $func, 'NOT ' ) !== false ) {
				$invert = true;
				$func   = str_replace( 'NOT ', '', $func );
			}
			$show_field = $func();
			if ( $invert ) {
				$show_field = ! $show_field;
			}
			if ( $show_field ) {
				return true;
			} else {
				return false;
			}
		}
		

		public
		function condition_applies(
			$args, $type = false
		) {
			$default_args = $this->default_args;
			$args         = wp_parse_args( $args, $default_args );

			if ( ! $type ) {
				if ( $args['condition'] ) {
					$type = 'condition';
				} elseif ( $args['callback_condition'] ) {
					$type = 'callback_condition';
				}
			}

			if ( ! $type || ! $args[ $type ] ) {
				return true;
			}

			//function callbacks
			$maybe_is_function = str_replace( 'NOT ', '', $args[ $type ] );
			if ( ! is_array( $args[ $type ] ) && ! empty( $args[ $type ] )
			     && function_exists( $maybe_is_function )
			) {
				return $this->function_callback_applies( $args[ $type ] );
			}

			$condition = $args[ $type ];

			//if we're checking the condition, but there's also a callback condition, check that one as well.
			//but only if it's an array. Otherwise it's a func.
			if ( $type === 'condition' && isset( $args['callback_condition'] )
			     && is_array( $args['callback_condition'] )
			) {
				$condition += $args['callback_condition'];
			}

			foreach ( $condition as $c_fieldname => $c_value_content ) {
				$c_values = $c_value_content;
				//the possible multiple values are separated with comma instead of an array, so we can add NOT.
				if ( ! is_array( $c_value_content )
				     && strpos( $c_value_content, ',' ) !== false
				) {
					$c_values = explode( ',', $c_value_content );
				}
				$c_values = is_array( $c_values ) ? $c_values
					: array( $c_values );

				foreach ( $c_values as $c_value ) {
					$maybe_is_function = str_replace( 'NOT ', '', $c_value );
					if ( function_exists( $maybe_is_function ) ) {
						$match = $this->function_callback_applies( $c_value );
						if ( ! $match ) {
							return false;
						}
					} else {
						$actual_value = $this->get_value( $c_fieldname );

						$fieldtype = $this->get_field_type( $c_fieldname );

						if ( strpos( $c_value, 'NOT ' ) === false ) {
							$invert = false;
						} else {
							$invert  = true;
							$c_value = str_replace( "NOT ", "", $c_value );
						}

						if ( $fieldtype == 'multicheckbox' ) {
							if ( ! is_array( $actual_value ) ) {
								$actual_value = array( $actual_value );
							}
							//get all items that are set to true
							$actual_value = array_filter( $actual_value,
								function ( $item ) {
									return $item == 1;
								} );
							$actual_value = array_keys( $actual_value );

							if ( ! is_array( $actual_value ) ) {
								$actual_value = array( $actual_value );
							}
							$match = false;
							foreach ( $c_values as $check_each_value ) {
								if ( in_array( $check_each_value,
									$actual_value )
								) {
									$match = true;
								}
							}

						} else {
							//when the actual value is an array, it is enough when just one matches.
							//to be able to return false, for no match at all, we check all items, then return false if none matched
							//this way we can preserve the AND property of this function
							$match = ( $c_value === $actual_value
							           || in_array( $actual_value,
									$c_values ) );

						}
						if ( $invert ) {
							$match = ! $match;
						}
						if ( ! $match ) {
							return false;
						}
					}

				}
			}

			return true;
		}

		public function get_field_type( $fieldname ) {
			if ( ! isset( BURST::$config->fields[ $fieldname ] ) ) {
				return false;
			}

			return BURST::$config->fields[ $fieldname ]['type'];
		}

		public
		function textarea(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<textarea name="<?php echo esc_html( $fieldname ) ?>"
                      <?php if ( $args['required'] ) {
	                      echo 'required';
                      } ?>
                        class="validation <?php if ( $args['required'] ) {
	                        echo 'is-required';
                        } ?>"
                      placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"><?php echo esc_html( $value ) ?></textarea>
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		/*
         * Show field with editor
         *
         *
         * */

		public
		function editor(
			$args, $step = ''
		) {
			$fieldname     = 'burst_' . $args['fieldname'];
			$args['first'] = true;
			$media         = $args['media'] ? true : false;

			$value = $this->get_value( $args['fieldname'], $args['default'] );

			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<?php
			$settings = array(
				'media_buttons' => $media,
				'editor_height' => 300,
				// In pixels, takes precedence and has no default value
				'textarea_rows' => 15,
			);
			wp_editor( $value, $fieldname, $settings ); ?>
			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function javascript(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			$value     = $this->get_value( $args['fieldname'],
				$args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<div id="<?php echo esc_html( $fieldname ) ?>editor"
			     style="height: 200px; width: 100%"><?php echo $value ?></div>
			<?php do_action( 'burst_after_field', $args ); ?>
			<script>
				var <?php echo esc_html( $fieldname )?> =
				ace.edit("<?php echo esc_html( $fieldname )?>editor");
				<?php echo esc_html( $fieldname )?>.setTheme("ace/theme/monokai");
				<?php echo esc_html( $fieldname )?>.session.setMode("ace/mode/javascript");
				jQuery(document).ready(function ($) {
					var textarea = $('textarea[name="<?php echo esc_html( $fieldname )?>"]');
					<?php echo esc_html( $fieldname )?>.
					getSession().on("change", function () {
						textarea.val(<?php echo esc_html( $fieldname )?>.getSession().getValue()
					)
					});
				});
			</script>
			<textarea style="display:none"
			          name="<?php echo esc_html( $fieldname ) ?>"><?php echo $value ?></textarea>
			<?php
		}

		public
		function css(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>

			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo $args['fieldname'] ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<div id="<?php echo esc_html( $fieldname ) ?>editor"
			     style="height: 200px; width: 100%"><?php echo $value ?></div>
			<?php do_action( 'burst_after_field', $args ); ?>
			<script>
				var <?php echo esc_html( $fieldname )?> =
				ace.edit("<?php echo esc_html( $fieldname )?>editor");
				<?php echo esc_html( $fieldname )?>.setTheme("ace/theme/monokai");
				<?php echo esc_html( $fieldname )?>.session.setMode("ace/mode/css");
				jQuery(document).ready(function ($) {
					var textarea = $('textarea[name="<?php echo esc_html( $fieldname )?>"]');
					<?php echo esc_html( $fieldname )?>.
					getSession().on("change", function () {
						textarea.val(<?php echo esc_html( $fieldname )?>.getSession().getValue()
					)
					});
				});
			</script>
			<textarea style="display:none"
			          name="<?php echo esc_html( $fieldname ) ?>"><?php echo $value ?></textarea>
			<?php
		}


		public
		function colorpicker(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}


			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo esc_html( $fieldname ) ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<input type="hidden" name="<?php echo esc_html( $fieldname ) ?>"
			       id="<?php echo esc_html( $fieldname ) ?>"
			       value="<?php echo esc_html( $value ) ?>"
			       class="burst-color-picker-hidden">
			<input type="text" name="color_picker_container"
			       data-hidden-input='<?php echo esc_html( $fieldname ) ?>'
			       value="<?php echo esc_html( $value ) ?>"
			       class="burst-color-picker"
			       data-default-color="<?php echo esc_html( $args['default'] ) ?>">
			<?php do_action( 'burst_after_field', $args ); ?>

			<?php
		}

        public
        function copy(
            $args
        ) {
            $fieldname = 'burst_' . $args['fieldname'];

            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>

            <?php do_action( 'burst_before_label', $args ); ?>
            <label
                    for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
            <?php do_action( 'burst_after_label', $args ); ?>
            <input <?php if ( $args['required'] ) {
                echo 'required';
            } ?>
                    placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                    type="text"
                    value="<?php echo esc_html( $args['copy_text'] ) ?>"
                    name="<?php echo esc_html( $fieldname ) ?>"
                    disabled>

            <span class="copy-container">
                <button type="button"
                        class="button button-medium burst-copy-button"
                        data-clipboard-text="<?php echo $args['copy_text']; ?>">
                    <?php _e('Copy', 'burst') ?>
                </button>
                <span class="success hidden" aria-hidden="true"><?php _e( 'Copied!' ); ?></span>
            </span>
            <?php do_action( 'burst_after_field', $args ); ?>
            <?php
        }


		public
		function step_has_fields(
			$page, $step = false, $section = false
		) {

			$fields = BURST::$config->fields( $page, $step, $section );
			foreach ( $fields as $fieldname => $args ) {
				$default_args = $this->default_args;
				$args         = wp_parse_args( $args, $default_args );

				$type              = ( $args['callback'] ) ? 'callback'
					: $args['type'];
				$args['fieldname'] = $fieldname;

				if ( $type == 'callback' ) {
					return true;
				} else {
					if ( $this->show_field( $args ) ) {
						return true;
					}
				}
			}

			return false;
		}

		/**
		* 
		*/
        public
        function get_fields(
            $source, $step = false, $section = false, $get_by_fieldname = false
        ) {

            $fields = BURST::$config->fields( $source, $step, $section, $get_by_fieldname );
            $i = 0;
            foreach ( $fields as $fieldname => $args ) {
                if ( $i === 0 ) {
                    $args['first'] = true;
                }
                $i ++;
                $default_args = $this->default_args;
                $args         = wp_parse_args( $args, $default_args );


                $type              = ( $args['callback'] ) ? 'callback' : $args['type'];
                $args['fieldname'] = $fieldname;
                switch ( $type ) {
					case 'callback':
						$this->callback( $args );
						break;
					case 'text':
						$this->text( $args );
						break;
					case 'button':
						$this->button( $args );
						break;
					case 'url':
						$this->url( $args );
						break;
					case 'select':
						$this->select( $args );
						break;
					case 'select2':
						$this->select2( $args );
						break;
                    case 'select_control':
                        $this->select_control( $args );
                        break;
                    case 'select_variant':
                        $this->select_variant( $args );
                        break;
					case 'colorpicker':
						$this->colorpicker( $args );
						break;
					case 'checkbox':
						$this->checkbox( $args );
						break;
					case 'textarea':
						$this->textarea( $args );
						break;
					case 'radio':
						$this->radio( $args );
						break;
					case 'multicheckbox':
						$this->multicheckbox( $args );
						break;
					case 'javascript':
						$this->javascript( $args );
						break;
					case 'css':
						$this->css( $args );
						break;
					case 'email':
						$this->email( $args );
						break;
					case 'phone':
						$this->phone( $args );
						break;
					case 'number':
						$this->number( $args );
						break;
					case 'notice':
						$this->notice( $args );
						break;
					case 'editor':
						$this->editor( $args, $step );
						break;
					case 'label':
						$this->label( $args );
						break;
					case 'weightslider';
						$this->weightslider( $args );
						break;
                    case 'copy':
                        $this->copy( $args );
                        break;
                    case 'date':
                        $this->date( $args );
                        break;
				}
			}

		}

		public
		function callback(
			$args
		) {
			$callback = $args['callback'];
			do_action( "burst_$callback", $args );
		}

		public
		function notice(
			$args
		) {
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			do_action( 'burst_before_label', $args );
			burst_notice( $args['label'], 'warning' );
			do_action( 'burst_after_label', $args );
			do_action( 'burst_after_field', $args );
		}

		public
		function select(
			$args
		) {

			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}

			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo esc_html( $fieldname ) ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<select <?php if ( $args['required'] ) {
				echo 'required';
			} ?> name="<?php echo esc_html( $fieldname ) ?>">
				<option value=""><?php _e( "Choose an option",
						'burst' ) ?></option>
				<?php foreach (
					$args['options'] as $option_key => $option_label
				) { ?>
					<option
						value="<?php echo esc_html( $option_key ) ?>" <?php echo ( $option_key
						                                                           == $value )
						? "selected"
						: "" ?>><?php echo esc_html( $option_label ) ?></option>
				<?php } ?>
			</select>

			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function select2(
			$args
		) { 
			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			$query_settings = json_encode($args['query_settings']);
			echo '<script> var '. $fieldname .'_query_settings = '. $query_settings .';</script>';

			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo esc_html( $fieldname ) ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?>
			</label>
			<?php do_action( 'burst_after_label', $args ); ?>

			<select class="burst-select2-page-field form-control" <?php if ( $args['required'] ) {
				echo 'required';
			} ?> name="<?php echo esc_html( $fieldname ) ?>">
				<?php if ($value) {
					$post = get_post($value);
					if($post){ ?>
						<option value="<?=$value?>">
						<?php echo $post->post_title ?></option>
				
					<?php }

				} else { ?> 
					<option value="">
					<?php _e( "Choose an option",
						'burst' ) ?></option>
				<?php } ?>
				
			</select>


			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

        public
        function select_control(
            $args
        ) {
            $fieldname = 'burst_' . $args['fieldname'];

            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            $query_settings = json_encode($args['query_settings']);
            echo '<script> var '. $fieldname .'_query_settings = '. $query_settings .';</script>';

            ?>
            <?php do_action( 'burst_before_label', $args ); ?>
            <label
                    for="<?php echo esc_html( $fieldname ) ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?>
            </label>
            <?php do_action( 'burst_after_label', $args ); ?>

            <?php burst_display_experiment_version('control'); ?>

            <select class="burst-select2-page-field form-control" <?php if ( $args['required'] ) {
                echo 'required';
            } ?> name="<?php echo esc_html( $fieldname ) ?>">
                <?php if ($value) {
                    $post = get_post($value);
                    if($post){ ?>
                        <option value="<?=$value?>">
                            <?php echo $post->post_title ?></option>

                    <?php }

                } else { ?>
                    <option value="">
                        <?php _e( "Choose an option",
                            'burst' ) ?></option>
                <?php } ?>

            </select>


            <?php do_action( 'burst_after_field', $args ); ?>
            <?php
        }

        public
        function select_variant(
            $args
        ) {
            $fieldname = 'burst_' . $args['fieldname'];

            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            $query_settings = json_encode($args['query_settings']);
            echo '<script> var '. $fieldname .'_query_settings = '. $query_settings .';</script>';

            ?>
            <?php do_action( 'burst_before_label', $args ); ?>
            <label
                    for="<?php echo esc_html( $fieldname ) ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?>
            </label>
            <?php do_action( 'burst_after_label', $args ); ?>

            <?php burst_display_experiment_version('variant'); ?>

            <select class="burst-select2-page-field form-control" <?php if ( $args['required'] ) {
                echo 'required';
            } ?> name="<?php echo esc_html( $fieldname ) ?>">
                <?php if ($value) {
                    $post = get_post($value);
                    if($post){ ?>
                        <option value="<?=$value?>">
                            <?php echo $post->post_title ?></option>

                    <?php }

                } else { ?>
                    <option value="0">
                        <?php _e( "Choose an option",
                            'burst' ) ?></option>
                <?php } ?>

            </select>
            <?php if (isset($_GET['action']) && $_GET['action'] == 'new'){ ?>
                <label>
                    <input name="<?php echo 'duplicate_' . esc_html( $fieldname ) ?>"
                           class = "<?php echo 'duplicate_' . esc_html( $fieldname ) ?>"
                           size="40" type="checkbox"
                           value="1" >
                    Duplicate Control and assign as Variant
                </label
            </div>
            <?php } ?>



            <?php do_action( 'burst_after_field', $args ); ?>
            <?php
        }

        public
        function date(
            $args
        ) {
            $fieldname = 'burst_' . $args['fieldname'];
            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ($value > 0) {
                $date = DateTime::createFromFormat( 'U', $value);
                $date_value = $date->format('Y-m-d');
            } else {
                $date_value = false;
            }

            $today = new DateTime('today');
            $year = new DateTime('+1 year');

            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>

            <?php do_action( 'burst_before_label', $args ); ?>
            <label
                    for="<?php echo $args['fieldname'] ?>"><?php echo $args['label'] ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
            <?php do_action( 'burst_after_label', $args ); ?>
            <input <?php if ( $args['required'] ) {
                echo 'required';
            } ?>
                    class="validation <?php if ( $args['required'] ) {
                        echo 'is-required';
                    } ?>"
                    placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                    type="date"
                    value="<?php echo $date_value; ?>"
                    min="<?php echo $today->format('Y-m-d'); ?>"
                    max="<?php echo $year->format('Y-m-d'); ?>"
                    name="<?php echo esc_html( $fieldname ) ?>">
            <?php do_action( 'burst_after_field', $args ); ?>
            <?php
        }

		public
		function label(
			$args
		) {

			$fieldname = 'burst_' . $args['fieldname'];
			if ( ! $this->show_field( $args ) ) {
				return;
			}

			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label
				for="<?php echo esc_html( $fieldname ) ?>"><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>

			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		/**
		 *
		 * Button/Action field
		 *
		 * @param $args
		 *
		 * @echo string $html
		 */

		public
		function button(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];
			if ( ! $this->show_field( $args ) ) {
				return;
			}

			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<?php if ( $args['post_get'] === 'get' ) { ?>
				<a <?php if ( $args['disabled'] )
					echo "disabled" ?>href="<?php echo $args['disabled']
					? "#"
					: admin_url( 'admin.php?page=burst-settings&action='
					             . $args['action'] ) ?>"
				   class="button"><?php echo esc_html( $args['label'] ) ?></a>
			<?php } else { ?>
				<input <?php if ( $args['warn'] )
					echo 'onclick="return confirm(\'' . $args['warn']
					     . '\');"' ?> <?php if ( $args['disabled'] )
					echo "disabled" ?> class="button" type="submit"
				                       name="<?php echo $args['action'] ?>"
				                       value="<?php echo esc_html( $args['label'] ) ?>">
			<?php } ?>

			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		public
		function save_button() {
			wp_nonce_field( 'burst_save', 'burst_nonce' );
			?>	
				<div class="burst-button">
					<input class="button button-primary" type="submit"
				       name="burst-save"
				       value="<?php _e( "Save", 'burst' ) ?>">
				</div>
			<?php
		}

		/**
		 *
		 * Weight slider field for dividing users over tests
		 *
		 * @param $args
		 *
		 * @echo string $html
		 */

		public
		function weightslider(
			$args
		) {
			$fieldname = 'burst_' . $args['fieldname'];

			$value = $this->get_value( $args['fieldname'], $args['default'] );
			if ( ! $this->show_field( $args ) ) {
				return;
			}
			?>
			<?php do_action( 'burst_before_label', $args ); ?>
			<label><?php echo esc_html( $args['label'] ) ?><?php echo $this->get_help_tip_btn( $args ); ?></label>
			<?php do_action( 'burst_after_label', $args ); ?>
			<div class="weightslider">
				<input  type="range" 
						value="<?php echo esc_html( $value ) ?>" 
						name="<?php echo esc_html( $fieldname ) ?>" 
						min="0" 
						max="100" 
						value="100" 
						step="10" 
						oninput="
							weightsliderValueIncluded.value=value;
							weightsliderValueNotIncluded.value= 100 - value;
						"
				</input>
				<p>
					Visitors included: <output name="<?php echo esc_html( $fieldname ) ?>" id="weightsliderValueIncluded"><?php echo intval( $value ) ?></output><span>%</span>
				</p><p>
					Not included: <output name="<?php echo esc_html( $fieldname ) ?>" id="weightsliderValueNotIncluded"><?php echo 100 - intval($value ) ?></output><span>%</span>
				</p>


			</div>


			<?php do_action( 'burst_after_field', $args ); ?>
			<?php
		}

		/**
		 * Get value of this fieldname
		 *
		 * @param        $fieldname
		 * @param string $default
		 *
		 * @return mixed
		 */

		public
		function get_value(
			$fieldname, $default = ''
		) {
		    error_log('get value for: ' . $fieldname);
			$fields = BURST::$config->fields();
			if ( ! isset( $fields[ $fieldname ] ) ) {
				return false;
			}

			$source = $fields[ $fieldname ]['source'];
			if ( strpos( $source, 'experiment' ) !== false ) {
				$id = false;

				error_log('experiment');
				if ( isset( $_GET['post'] ) ) {
					$post_id = intval( $_GET['post'] );
					$id = burst_get_experiment_id_for_post($post_id);
				}  else if ( isset( $_GET['experiment_id'] ) ) {
					$id = intval( $_GET['experiment_id'] );
				} else if ( isset( $_POST['experiment_id'] ) ) {
					$id = intval( $_POST['experiment_id'] );
				}

				$experiment = new BURST_EXPERIMENT( $id );
				error_log(print_r($experiment, true));
				$value  = ! empty( $experiment->{$fieldname} )
					? $experiment->{$fieldname} : false;

			} else {
                error_log('options');
				$options = get_option( 'burst_options_' . $source );
				$value   = isset( $options[ $fieldname ] )
					? $options[ $fieldname ] : false;
			}

			//if no value isset, pass a default
			$value = ( $value !== false ) ? $value
				: apply_filters( 'burst_default_value', $default, $fieldname );

			return $value;
		}

		/**
		 * Checks if a fieldname exists in the burst field list.
		 *
		 * @param string $fieldname
		 *
		 * @return bool
		 */

		public
		function sanitize_fieldname(
			$fieldname
		) {
			$fields = BURST::$config->fields();
			if ( array_key_exists( $fieldname, $fields ) ) {
				return $fieldname;
			}

			return false;
		}


		public
		function get_comment(
			$args
		) {
			if ( ! isset( $args['comment'] ) ) {
				return;
			}
			?>
			<div class="burst-comment"><?php echo $args['comment'] ?></div>
			<?php
		}

		/**
		 *
		 * returns the button with which a user can open the help modal
		 *
		 * @param array $args
		 *
		 * @return string
		 */

		public
		function get_help_tip_btn(
			$args
		) {
			$output = '';
			if ( isset( $args['tooltip'] ) ) {
				$output
					= '<span data-text="'. wp_kses_post( $args['tooltip'] ) .'" class="burst-tooltip left"><img width="15px" src="'. trailingslashit(burst_url) .'assets/icons/question-circle-solid.svg"></span>';
			}

			return $output;
		}

		/**
		 * returns the modal help window
		 *
		 * @param array $args
		 *
		 * @return string
		 */

		public
		function get_help_tip(
			$args
		) {
			$output = '';
			if ( isset( $args['help'] ) ) {
				$output
					= '<div><div class="burst-help-modal "><span><i class="fa fa-times"></i></span>'
					  . wp_kses_post( $args['help'] ) . '</div></div>';
			}


			return $output;
		}


		/*
         * Check if all required fields are answered
         *
         *
         *
         * */

		public
		function step_complete(
			$step
		) {

		}


		/*
         * Check if all required fields in a section are answered
         *
         *
         * */

		public
		function section_complete(
			$section
		) {

		}


		public
		function has_errors() {
			if ( count( $this->form_errors ) > 0 ) {
				return true;
			}


			return false;
		}


	}
} //class closure
