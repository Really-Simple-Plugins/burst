<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! function_exists( 'burst_user_can_manage' ) ) {
	/**
	 * Check if user has Burst permissions 
	 * @return boolean true or false
	 */
	function burst_user_can_manage() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'burst_get_experiments' ) ) {

	/**
	 * Get array of experiment objects
	 *
	 * @param array $args
	 *
	 * @return array
	 */

	function burst_get_experiments( $args = array() ) {
		$defaults = array(
			'order'  => 'DESC',
			'orderby' => 'date_modified',
		);
		$args = wp_parse_args( $args, $defaults );
		$sql  = '';

		$orderby = sanitize_title($args['orderby']);

		$order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
		global $wpdb;

		// array with multiple post statuses
		if ( isset( $args['status'] ) && is_array($args['status']) ) {
			foreach ($args['status'] as $status) {
				$status = burst_sanitize_experiment_status($status);
				$statuses[] = "'".$status."'";
			}
			$statuses = implode (", ", $statuses);
			$sql .= "AND status IN ($statuses)";

		// one post staus as a string		
		} else if ( isset( $args['status'] ) ) {
			$status = burst_sanitize_experiment_status($args['status']);
			$sql .= " AND status = '$status'";
		} 

		$sql .= " ORDER BY $orderby $order";

		return  $wpdb->get_results( "select * from {$wpdb->prefix}burst_experiments where 1=1 $sql" );
	}
}

if ( !function_exists('burst_get_default_experiment_id')){
	/**
	 * Get the default experiment id
	 * @return bool|int
	 */
	function burst_get_default_experiment_id(){
		$experiments = burst_get_experiments();
		if ( $experiments && is_array($experiments) ) {
			$experiments = reset($experiments);
			return $experiments->ID;
		} else {
			return false;
		}
	}
}

if ( !function_exists( 'burst_setcookie') ) {
	function burst_setcookie( $key, $value, $expiration_days ){
		$options = array (
			'expires' => time() + (DAY_IN_SECONDS * apply_filters('burst_cookie_retention', $expiration_days) ),
			'path' => '/',
			'secure' => is_ssl(),
			'samesite' => 'Lax' // None || Lax  || Strict
		);

		setcookie($key, $value, $options );
	}
}

if ( !function_exists( 'burst_sanitize_experiment_status' )) {
	/**
	 * Sanitize the status
	 * @param string $status
	 *
	 * @return string
	 */
	function burst_sanitize_experiment_status($status) {
		$statuses = array(
			'draft',
			'active',
			'completed',
			'archived',
		);
		if ( in_array( $status, $statuses )) {
			return $status;
		} else {
			return 'draft';
		}
	}
}

if ( !function_exists('burst_experiment_not_reached_sample_size')) {
	/**
	 * Check if any of the active expirements is running 30 days, and has not reached the sample size yet.
	 *
	 * @return bool
	 */
	function burst_experiment_not_reached_sample_size(){
		$experiments = burst_get_experiments( array(
			'status' => 'active',
		));
		foreach ( $experiments as $experiment ) {
			$experiment = new BURST_EXPERIMENT($experiment->ID);
			$one_month_ago = strtotime('-30 days');
			if ($one_month_ago > $experiment->date_started && !$experiment->has_reached_minimum_sample_size() ) {
				return true;
			}
		}

		return false;
	}
}

if (!function_exists('burst_read_more')) {
	/**
	 * Create a generic read more text with link for help texts.
	 *
	 * @param string $url
	 * @param bool   $add_space
	 *
	 * @return string
	 */
	function burst_read_more( $url, $add_space = true ) {
		$html
			= sprintf( __( "For more information on this subject, please read this %sarticle%s",
			'burst' ), '<a target="_blank" href="' . $url . '">',
			'</a>' );
		if ( $add_space ) {
			$html = '&nbsp;' . $html;
		}

		return $html;
	}
}

if ( ! function_exists( 'burst_get_template' ) ) {
	/**
	 * Get a template based on filename, overridable in theme dir
	 * @param $filename
	 *
	 * @return string
	 */

	function burst_get_template( $filename , $args = array() ) {

		$file       = trailingslashit( burst_path ) . 'templates/' . $filename;
		$theme_file = trailingslashit( get_stylesheet_directory() )
		              . trailingslashit( basename( burst_path ) )
		              . 'templates/' . $filename;

		if ( file_exists( $theme_file ) ) {
			$file = $theme_file;
		}

		if ( !file_exists($file) ) {
			return false;
		}

		if ( strpos( $file, '.php' ) !== false ) {
			ob_start();
			require $file;
			$contents = ob_get_clean();
		} else {
			$contents = file_get_contents( $file );
		}

		if ( !empty($args) && is_array($args) ) {
			foreach($args as $fieldname => $value ) {
				$contents = str_replace( '{'.$fieldname.'}', $value, $contents );
			}
		}

		return $contents;
	}
}

if ( ! function_exists( 'burst_array_filter_multidimensional' ) ) {
	function burst_array_filter_multidimensional(
		$array, $filter_key, $filter_value
	) {
		$new = array_filter( $array,
			function ( $var ) use ( $filter_value, $filter_key ) {
				return isset( $var[ $filter_key ] ) ? ( $var[ $filter_key ]
				                                        == $filter_value )
					: false;
			} );

		return $new;
	}
}

if ( !function_exists('burst_generate_test_data')){
	/**
	 * Function to be used only for testing purposes.
	 * It will fill the database with random data.
	 * - create an experiment.
	 * - Get that experiment's id
	 * - prefill the experiment id below.
	 */
	function burst_generate_test_data(){
		global $wpdb;


		$experiment_id = 10;

		for ( $i=30;$i>=0;$i--){
			$daytime = strtotime("-$i days");
			//generate random nr of hits between 4 and 100
			$hitcount = rand(0,100);
			for ( $hits=0;$hits<$hitcount;$hits++){
				//divide day in seconds, equally divided by hits numer
				$between_hits = round(DAY_IN_SECONDS / ($hitcount+1), 0);
				$time = $daytime + ($hits * $between_hits);

				$test_versions = array(
					'control',
					'variant',
				);
				$test_version_id = rand(0,1);
				$conversion = rand(0,6);
				$conversion = ($conversion==4);
				$burst_uid = 'test_uid_'.time();
				$test_version = $test_versions[$test_version_id];
				$url = site_url();

				$update_array = array(
					'page_url'            		=> $url,
					'time'               		=> $time,
					'uid'               		=> $burst_uid,
					'test_version'				=> $test_version,
					'experiment_id'				=> $experiment_id,
					'conversion'				=> $conversion,
				);
				$wpdb->insert(
					$wpdb->prefix . 'burst_statistics',
					$update_array
				);
			}
		}


	}
}
//burst_generate_test_data();

/**
 * Callback to ajax load the posts dropdown in the metabox
 *
 */
function burst_get_posts_ajax_callback(){
 	if (!burst_user_can_manage()) return;

	$return = array();
 	$query_settings = array();
 	foreach ( $_GET['query_settings'] as $key => $value ) {
	    $key = sanitize_text_field($key);
	    $value = sanitize_text_field($value);
	    $query_settings[$key] = $value;
	    error_log(print_r($query_settings, true));
    }

 	$default_args = array(
		's'=> sanitize_text_field( $_GET['q'] ),
		'post_type'=> sanitize_text_field( $query_settings['post_type']),
		'posts_per_page' => 25
	);

	$args = array_merge($default_args, $query_settings);

	$search_results = new WP_Query( $args );
	if( $search_results->have_posts() ) :
		while( $search_results->have_posts() ) : $search_results->the_post();	
			// shorten the title a little
			$title = ( mb_strlen( $search_results->post->post_title ) > 50 ) ? mb_substr( $search_results->post->post_title, 0, 49 ) . '...' : $search_results->post->post_title;
			$return[] = array( $search_results->post->ID, $title ); // array( Post ID, Post Title )
		endwhile;
	endif;
	echo json_encode( $return );
	die;
}
add_action( 'wp_ajax_burst_get_posts', 'burst_get_posts_ajax_callback' ); // wp_ajax_{action}

if ( ! function_exists( 'burst_localize_date' ) ) {

	function burst_localize_date( $date ) {
		$month             = date( 'F', strtotime( $date ) ); //june
		$month_localized   = __( $month ); //juni
		$date              = str_replace( $month, $month_localized, $date );
		$weekday           = date( 'l', strtotime( $date ) ); //wednesday
		$weekday_localized = __( $weekday ); //woensdag
		$date              = str_replace( $weekday, $weekday_localized, $date );

		return $date;
	}
}

if ( ! function_exists( 'burst_display_date' ) ) {

	function burst_display_date( $date ) {
		$display_date = date_i18n(get_option( 'date_format' ), $date);
		return $display_date;
	}
}

/**
 * Generate a random string, using a cryptographically secure 
 * pseudorandom number generator (random_int)
 *
 * This function uses type hints now (PHP 7+ only), but it was originally
 * written for PHP 5 as well.
 * 
 * For PHP 7, random_int is a PHP core function
 * For PHP 5.x, depends on https://github.com/paragonie/random_compat
 * 
 * @param int $length      How many characters do we want?
 * @param string $keyspace A string of all possible characters
 *                         to select from
 * @return string
 */

if ( ! function_exists( 'burst_random_str' ) ) {
	function burst_random_str(
	    int $length = 64,
	    string $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'
	): string {
	    if ($length < 1) {
	        throw new \RangeException("Length must be a positive integer");
	    }
	    $pieces = [];
	    $max = mb_strlen($keyspace, '8bit') - 1;
	    for ($i = 0; $i < $length; ++$i) {
	        $pieces []= $keyspace[random_int(0, $max)];
	    }
	    return implode('', $pieces);
	}
}

if ( ! function_exists( 'burst_post_has_experiment' ) ) {

	/**
	 * Check if post has experiment attached
	 * @param int|bool $post_id
	 *
	 * @return bool
	 */
	
	function burst_post_has_experiment($post_id = false){
		if (!$post_id) {
			$post_id = burst_get_current_post_id();			
		}
		if (!$post_id) return false;

		$experiment_id = get_post_meta($post_id, 'burst_experiment_id');
		return intval($experiment_id) ? true : false;
	}

}

if ( ! function_exists( 'burst_get_experiment_id_for_post' ) ) {

	/**
	 * Check if post has experiment attached
	 * @param int|bool $post_id
	 *
	 * @return bool
	 */
	

	function burst_get_experiment_id_for_post( $post_id = false ){
		if (!$post_id) {
			$post_id = burst_get_current_post_id();			
		}

		if (!$post_id) return false;

		return get_post_meta($post_id, 'burst_experiment_id', true);
	}

}

if ( !function_exists( 'burst_sanitize_test_version' )) {
	/**
	 * Sanitize the test version
	 *
	 * @param string $str
	 *
	 * @return string
	 */

	function burst_sanitize_test_version( $str ) {
		$test_versions = array(
			'variant',
			'control'
		);

		if ( in_array( $str, $test_versions ) ) {
			return $str;
		} else {
			return 'control';
		}
	}
}

if ( ! function_exists( 'burst_get_current_post_type' ) ) {

	/**
	 * Get the current post type
	 * @param int $post_id
	 *
	 * @return string|bool
	 */
	
	function burst_get_current_post_type($post_id = false){
		if (!$post_id) {
			$post_id = burst_get_current_post_id();			
		}
		if (!$post_id) return false;

		$post = get_post($post_id);
		if (!$post) return false;

		return $post->post_type;
	}

}

if ( ! function_exists( 'burst_get_current_post_id' ) ) {

	/**
	 * Get the current post type
	 * @param $post_id
	 *
	 * @return string
	 */
	
	function burst_get_current_post_id(){
		$post_id = get_the_ID();
		
		if ( !$post_id ){
			$post_id = isset($_GET['post']) && is_numeric($_GET['post']) ? intval($_GET['post']) : false;
		}

		return $post_id;
	}
}

if ( !function_exists( 'burst_get_current_url') ) {
	/**
	 * Function to get the current URL used in the load_experiment_content function
	 * @return string The current URL
	 */
	function burst_get_current_url() {
		return parse_url( get_permalink(), PHP_URL_PATH );
	}

}

if ( ! function_exists( 'burst_get_all_post_statuses' ) ) {

	/**
	 * Get the current post type
	 * @param $post_id
	 *
	 * @return array
	 */
	
	function burst_get_all_post_statuses($exceptions = array()){
		$post_statuses = get_post_stati();
		
		$filtered_post_statuses = array();
		foreach ($post_statuses as $post_status => $value) {
			if (!in_array($post_status, $exceptions)) {
				$filtered_post_statuses[] = $post_status;
			}
		}
		$filtered_post_statuses[] = 'experiment';
		
		return $filtered_post_statuses;
	}

}
if ( ! function_exists( 'burst_display_experiment_status' ) ) {

	function burst_display_experiment_status($experiment_status, $get_array = false) {
		switch( $experiment_status ) {
				case 'archived':
					$status_text = __( 'Archived', 'burst' );
					$class = 'grey';
					break;
				case 'active':
					$class = 'rsp-blue-yellow';
					$status_text = __( 'Active', 'burst' );
					break;
				case 'completed':
					$status_text = __( 'Completed', 'burst' );
					$class = 'rsp-green';
					break;
				case 'loading':
					$status_text = __( 'Loading...', 'burst' );
					$class = 'grey loading initial-loading';
					break;
				case 'draft':
				default:
					$status_text = __( 'Draft', 'burst' );
					$class = 'grey';
					break;
			}
			$status = false;
			if ($get_array) {
				$status = array(
					'class' => $class,
					'title' => $status_text,
				);
			} else {
				$status =  '<div class="burst-experiment-status"><span class="burst-bullet ' . $class . '"></span><span class="burst-experiment-status__text">' . $status_text . '</span></div>';
			}
			
			return $status;
	}
}

if ( ! function_exists( 'burst_display_experiment_version' ) ) {

    function burst_display_experiment_version($version = 'control') {

        ob_start(); ?>
        <div class="burst-experiment-version <?php echo $version ?>">
            <span class="burst-experiment-dot <?php echo $version ?>"></span>
            <div class="burst-experiment-version__title">
                <p><?php echo $version ?></p>
            </div>
        </div>

        <?php
        $html = ob_get_clean();
        echo $html;
    }
}


if ( ! function_exists( 'burst_get_report_url' ) ) {
	/**
	 * Get the URL that leads to the dashboard and show data for the experiment ID
	 * @param  int $experiment_id
	 * @return string Url to the dashboard
	 */
	function burst_get_report_url($experiment_id) {
		return admin_url('admin.php?page=burst&experiment_id='. $experiment_id .'');
	}
}

if ( ! function_exists( 'burst_get_value' ) ) {

    /**
     * Get value for an a burst option
     * For usage very early in the execution order, use the $page option. This bypasses the class usage.
     *
     * @param string $fieldname
     * @param bool|int $post_id
     * @param bool|string $page
     * @param bool $use_default
     * @param bool $use_translate
     *
     * @return array|bool|mixed|string
     */

    function burst_get_value(
        $fieldname, $post_id = false, $page = false, $use_default = true, $use_translate = true
    ) {
        if ( ! is_numeric( $post_id ) ) {
            $post_id = false;
        }

        if ( ! $page && ! isset( BURST::$config->fields[ $fieldname ] ) ) {
            return false;
        }

        //if  a post id is passed we retrieve the data from the post
        if ( ! $page ) {
            $page = BURST::$config->fields[ $fieldname ]['source'];
        }
        if ( $post_id && ( $page !== 'wizard' ) ) {
            $value = get_post_meta( $post_id, $fieldname, true );
        } else {
            $fields = get_option( 'burst_options_' . $page );

            $default = ( $use_default && $page && isset( BURST::$config->fields[ $fieldname ]['default'] ) )
                ? BURST::$config->fields[ $fieldname ]['default'] : '';
            //@todo $default = apply_filters( 'burst_default_value', $default, $fieldname );

            $value   = isset( $fields[ $fieldname ] ) ? $fields[ $fieldname ] : $default;
        }

        /*
         * Translate output
         *
         * */
        if ($use_translate) {

            $type = isset(BURST::$config->fields[$fieldname]['type'])
                ? BURST::$config->fields[$fieldname]['type'] : false;
            if ($type === 'cookies' || $type === 'thirdparties'
                || $type === 'processors'
            ) {
                if (is_array($value)) {

                    //this is for example a cookie array, like ($item = cookie("name"=>"_ga")

                    foreach ($value as $item_key => $item) {
                        //contains the values of an item
                        foreach ($item as $key => $key_value) {
                            if (function_exists('pll__')) {
                                $value[$item_key][$key] = pll__($item_key . '_'
                                    . $fieldname
                                    . "_" . $key);
                            }
                            if (function_exists('icl_translate')) {
                                $value[$item_key][$key]
                                    = icl_translate('burst',
                                    $item_key . '_' . $fieldname . "_" . $key,
                                    $key_value);
                            }

                            $value[$item_key][$key]
                                = apply_filters('wpml_translate_single_string',
                                $key_value, 'burst',
                                $item_key . '_' . $fieldname . "_" . $key);
                        }
                    }
                }
            } else {
                if (isset(BURST::$config->fields[$fieldname]['translatable'])
                    && BURST::$config->fields[$fieldname]['translatable']
                ) {
                    if (function_exists('pll__')) {
                        $value = pll__($value);
                    }
                    if (function_exists('icl_translate')) {
                        $value = icl_translate('burst', $fieldname, $value);
                    }

                    $value = apply_filters('wpml_translate_single_string', $value,
                        'burst', $fieldname);
                }
            }

        }

        return $value;
    }
}

if ( ! function_exists( 'burst_intro' ) ) {

    /**
     * @param string $msg
     *
     * @return string|void
     */

    function burst_intro( $msg ) {
        if ( $msg == '' ) {
            return;
        }
        $html = "<div class='burst-panel burst-notification burst-intro'>{$msg}</div>";

        echo $html;

    }
}

if ( ! function_exists( 'burst_notice' ) ) {
    /**
     * Notification without arrow on the left. Should be used outside notifications center
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool   $echo
     *
     * @return string|void
     */
    function burst_notice( $msg, $type = 'notice', $echo = true ) {
        if ( $msg == '' ) {
            return;
        }

        $html = "<div class='burst-panel-wrap'><div class='burst-panel burst-notification burst-{$type}'><div>{$msg}</div></div></div>";

        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }
}

if ( ! function_exists( 'burst_conclusion' ) ) {
    /**
     * Conclusion list drop down
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool   $echo
     *
     * @return string|void
     */
    function burst_conclusion( $title, $conclusions, $animate = true, $echo = true ) {
        if ( is_array( $conclusions ) == false ) {
            return;
        }

        ob_start();

        echo '<div id="burst-conclusion"><h3>' . $title . '</h3><ul class="burst-conclusion__list">';
        foreach($conclusions as $conclusion) {
            $icon = $animate ? 'icon-loading' : 'icon-' . $conclusion['report_status'];
            $displayOpac = $animate ? 'style="opacity: 0"' : '';
            $display = $animate ? 'style="display: none"' : '';
            echo '<li ' . $displayOpac . 'class="burst-conclusion__check '  .$icon . '" data-status="' . $conclusion['report_status'] . '">';
            if ($animate) echo '<p class="burst-conclusion__check--check-text">' . $conclusion['check_text'] . '</p>';
            echo '<p ' . $display . ' class="burst-conclusion__check--report-text">' . $conclusion['report_text'] . '</p>';
            echo '</li>';
        }
        echo '</ul></div>';
        if ($animate) {
            ?>
            <script>
                jQuery('.burst-conclusion__check--report-text').hide();
                // We initialise this to the first text element
                var firstText = jQuery(".burst-conclusion__check:first-child");
                var time = 0;
                var timeSmall = 0;

                jQuery(".burst-conclusion__check").each(function(){
                    console.log(this);
                    jQuery(firstText).css('opacity', 1);
                    var that = this;
                    time += getRandomInt(5, 10) * 100;;
                    setTimeout( function(){
                        setTimeout( function() {

                            //jQuery(that).text(jQuery(that).data('text'));

                            jQuery(that).removeClass('icon-loading').addClass('icon-' + jQuery(that).data('status'));
                            jQuery(that).find('.burst-conclusion__check--check-text').hide();
                            jQuery(that).find('.burst-conclusion__check--report-text').show();
                            jQuery(that).next().css('opacity', 1);
                        }, timeSmall );
                        timeSmall = getRandomInt(5, 10) * 100;
                    }, time);

                    console.log(Math.floor(time));
                });

                function getRandomInt(min, max) {
                    min = Math.ceil(min);
                    max = Math.floor(max);
                    return Math.floor(Math.random() * (max - min) + min); //The maximum is exclusive and the minimum is inclusive
                }

            </script>
            <?php
        }
        $html = ob_get_clean();

        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }
}

if ( ! function_exists( 'burst_sidebar_notice' ) ) {
    /**
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool|array  $condition
     *
     * @return string|void
     */

    function burst_sidebar_notice( $msg, $type = 'notice', $condition = false ) {
        if ( $msg == '' ) {
            return;
        }

        // Condition
        $condition_check = "";
        $condition_question = "";
        $condition_answer = "";
        $burst_hidden = "";
        if ($condition) {
            //get first
            $questions = array_keys($condition);
            $question = reset($questions);
            $answer = reset($condition);
            $condition_check = "condition-check-1";
            $condition_question = "data-condition-question-1='{$question}'";
            $condition_answer = "data-condition-answer-1='{$answer}'";
            $args = array('condition'=> $condition);
            $burst_hidden = burst_field::this()->condition_applies( $args ) ? "" : "burst-hidden";;
        }

        echo "<div class='burst-help-modal burst-notice burst-{$type} {$burst_hidden} {$condition_check}' {$condition_question} {$condition_answer}>{$msg}</div>";
    }
}

if ( !function_exists('burst_admin_notice')) {
    /**
     * @param $msg
     */
    function burst_admin_notice( $msg ) {
        /**
         * Prevent notice from being shown on Gutenberg page, as it strips off the class we need for the ajax callback.
         *
         * */
        $screen = get_current_screen();
        if ( $screen && $screen->parent_base === 'edit' ) {
            return;
        }
        ?>
        <div id="message"
             class="updated fade notice is-dismissible burst-admin-notice really-simple-plugins"
             style="border-left:4px solid #333">
            <div class="burst-admin-notice-container">
                <div class="burst-logo"><img width=80px"
                                             src="<?php echo burst_url ?>assets/images/icon-logo.svg"
                                             alt="logo">
                </div>
                <div style="margin-left:30px">
                    <?php echo $msg ?>
                </div>
            </div>
        </div>
        <?php

    }
}

if ( ! function_exists( 'burst_panel' ) ) {

    function burst_panel($title, $html, $custom_btn = '', $validate = '', $echo = true, $open = false) {
        if ( $title == '' ) {
            return '';
        }

        $open_class = $open ? 'style="display: block;"' : '';

        $output = '
        <div class="burst-panel burst-slide-panel burst-toggle-active">
            <div class="burst-panel-title">

                <span class="burst-panel-toggle">
                    '. burst_icon('arrow-right', 'success') .'
                    <span class="burst-title">' . $title . '</span>
                 </span>

                <span>' . $validate . '</span>

                <span>' . $custom_btn . '</span>

            </div>
            <div class="burst-panel-content" ' . $open_class . '>
                ' . $html . '
            </div>
        </div>';

        if ( $echo ) {
            echo $output;
        } else {
            return $output;
        }

    }
}

if ( ! function_exists( 'burst_list_item' ) ) {

    function burst_list_item( $title, $link, $btn, $selected ) {
        if ( $title == '' ) {
            return;
        }
        $selected = $selected ? "selected" : '';
        ?>

        <div class="burst-panel burst-link-panel <?php echo $selected ?>">
            <div class="burst-panel-title">
                <a class="burst-panel-link" href="<?php echo $link ?>">
                <span class="burst-panel-toggle">
                    <i class="fa fa-edit"></i>
                    <span class="burst-title"><?php echo $title ?></span>
                 </span>
                </a>

                <?php echo $btn ?>
            </div>
        </div>
        <?php

    }
}

if ( ! function_exists( 'burst_update_option' ) ) {
    /**
     * Save a burst option
     * @param string $page
     * @param string $fieldname
     * @param mixed $value
     */
    function burst_update_option( $page, $fieldname, $value ) {
        $options               = get_option( 'burst_options_' . $page );
        $options[ $fieldname ] = $value;
        if ( ! empty( $options ) ) {
            update_option( 'burst_options_' . $page, $options );
        }
    }
}
