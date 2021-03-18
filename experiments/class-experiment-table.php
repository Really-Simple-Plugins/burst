<?php
/**
 * Experiments Reports Table Class
 *
 *
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load WP_List_Table if not loaded
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class burst_experiment_Table extends WP_List_Table {

	/**
	 * Number of items per page
	 *
	 * @var int
	 * @since 1.5
	 */
	public $per_page = 50;

	/**
	 * Number of customers found
	 *
	 * @var int
	 * @since 1.7
	 */
	public $count = 0;

	/**
	 * Total customers
	 *
	 * @var int
	 * @since 1.95
	 */
	public $total = 0;

	/**
	 * The arguments for the data set
	 *
	 * @var array
	 * @since  2.6
	 */
	public $args = array();

	/**
	 * Get things started
	 *
	 * @since 1.5
	 * @see   WP_List_Table::__construct()
	 */


	public function __construct() {
		global $status, $page;

		// Set parent defaults
		parent::__construct( array(
			'singular' => __( 'experiment', 'burst' ),
			'plural'   => __( 'experiments', 'burst' ),
			'ajax'     => false,
		) );
	}

	/**
	 * Show the search field
	 *
	 * @param string $text     Label for the search box
	 * @param string $input_id ID of the search box
	 *
	 * @return void
	 * @since 1.7
	 *
	 */

	
	public function search_box( $text, $input_id ) {
        if ( empty( $_REQUEST['s'] ) && ! $this->has_items() ) {
            return;
        }
		$input_id = $input_id . '-search-input';

		if ( ! empty( $_REQUEST['orderby'] ) ) {
			echo '<input type="hidden" name="orderby" value="' . esc_attr( $_REQUEST['orderby'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['order'] ) ) {
			echo '<input type="hidden" name="order" value="' . esc_attr( $_REQUEST['order'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['post_mime_type'] ) ) {
			echo '<input type="hidden" name="post_mime_type" value="' . esc_attr( $_REQUEST['post_mime_type'] ) . '" />';
		}
		if ( ! empty( $_REQUEST['detached'] ) ) {
			echo '<input type="hidden" name="detached" value="' . esc_attr( $_REQUEST['detached'] ) . '" />';
		}
		?>
		<p class="search-box">
			<label class="screen-reader-text" for="<?php echo esc_attr( $input_id ); ?>"><?php echo $text; ?>:</label>
			<input type="search" id="<?php echo esc_attr( $input_id ); ?>" name="s" value="<?php _admin_search_query(); ?>" />
			<?php submit_button( $text, '', '', false, array( 'id' => 'search-submit' ) ); ?>
		</p>
		<?php

    }

	protected function get_views() { 
	    $status_links = array(
	        "all"       	=> '<a href="'. add_query_arg( array( 'page' => 'burst-experiments' ), admin_url('admin.php') ) . '">' . __("All", "burst") .'</a>',
	        "completed"   	=> '<a href="'. add_query_arg( array( 'page' => 'burst-experiments', 'status' => 'completed' ), admin_url('admin.php') ) . '">' . __("Completed", "burst") .'</a>',
	        "active" 		=> '<a href="'. add_query_arg( array( 'page' => 'burst-experiments', 'status' => 'active' ), admin_url('admin.php') ) . '">'.__("Active", "burst") .'</a>',
	        "draft" 		=> '<a href="'. add_query_arg( array( 'page' => 'burst-experiments', 'status' => 'draft' ), admin_url('admin.php') ) . '">'.__("Draft", "burst") .'</a>',
	        "archived"   	=> '<a href="'. add_query_arg( array( 'page' => 'burst-experiments', 'status' => 'archived' ), admin_url('admin.php') ) . '">'.__("Archived", "burst") .'</a>',
	        
	    );
	    return $status_links;
	}
	

	/**
	 * Gets the name of the primary column.
	 *
	 * @return string Name of the primary column.
	 * @since  2.5
	 * @access protected
	 *
	 */
	protected function get_primary_column_name() {
		return __( 'Name', 'burst' );
	}


    /**
     * Output the checkbox column
     *
     * @access      private
     * @since       7.1.2
     * @return      string
     */

    function column_cb( $item ) {

        return sprintf(
            '<input type="checkbox" name="%1$s_id[]" value="%2$s" />',
	        esc_attr( $this->_args['singular'] ),
            esc_attr( $item['ID'] ),
        );


    }

    /**
     * Setup available bulk actions
     *
     * @access      private
     * @since       7.1.2
     * @return      array
     */

    function get_bulk_actions() {

        $actions = array(
            'delete'     => __( 'Delete', 'burst' ),
        );

        return $actions;

    }

    /**
     * Process bulk actions
     *
     * @access      private
     * @since       7.1.2
     * @return      void
     */
    function process_bulk_action() {
     	if ( ! burst_user_can_manage() ) {
			return;
		}
        if( !isset($_GET['_wpnonce']) || ! wp_verify_nonce( $_GET['_wpnonce'], 'bulk-records' ) ) {
            return;
        }
        $ids = isset( $_GET['experiment_id'] ) ? $_GET['experiment_id'] : false;

        if( ! $ids ) {
            return;
        }

        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        foreach ( $ids as $id ) {
            if ( 'delete' === $this->current_action() ) {
                $experiment = new BURST_EXPERIMENT(intval($id));
                $experiment->delete();
            }
        }
    }


	public function column_title( $item ) {
		$title = ! empty( $item['title'] ) ? $item['title']
			: '<em>' . __( 'Unnamed experiment', 'burst' )
			  . '</em>';
		$title = apply_filters( 'burst_experiment_title', $title );

		$actions = array(
            			'delete' => '<a class="burst-experiment-action" data-action="delete" data-id="' . $item['ID']
			                        . '" href="#">' . __( 'Delete', 'burst' )
			                        . '</a>',
                        'archive' => '<a class="burst-experiment-action" data-action="archive" data-id="' . $item['ID']
                                    . '" href="#">' . __( 'Archive', 'burst' )
                                    . '</a>'
		);

		return $title . $this->row_actions( $actions );
	}

	public function column_status( $item ) {
		switch( $item['status'] ) {
			case 'archived':
				$status = __( 'Archived', 'burst' );
				$color = 'grey';
				break;
			case 'active':
				$color = 'rsp-blue-yellow';
				$status = __( 'Active', 'burst' );
				break;
			case 'completed':
				$status = __( 'Completed', 'burst' );
				$color = 'rsp-green';
				break;
			case 'draft':
			default:
				$status = __( 'Draft', 'burst' );
				$color = 'grey';
				break;
		}
		$status =  '<span class="burst-bullet ' . $color . '"></span><span>' . $status . '</span>';
		return apply_filters( 'burst_experiment_status', $status );
	}

	public function column_goals( $item ) {
		$goals = ! empty( $item['goals'] ) ? $item['goals']
			: '<em>' . __( 'No KPI selected', 'burst' )
			  . '</em>';
		$goals = apply_filters( 'burst_experiment_goals', $goals );

		return $goals;
	}

	public function column_control_id( $item ) {
		$post = get_post($item['control_id']);
		$control_id = $item['control_id'] ? $post->post_title : __( 'No control ID', 'burst' );
		$control_id .= '</br><span style="color: grey; ">/'.$post->post_name.'</span>';
		$control_id = apply_filters( 'burst_experiment_control_id', $control_id );

		$actions = array(
			'edit'   => '<a href="' . admin_url( 'post.php?post=' . $item['control_id'] ) . '&action=edit">' . __( 'Edit control post', 'burst' ) . '</a>',
		);
		return $control_id . $this->row_actions( $actions );
	}

	public function column_variant_id( $item ) {
		$post = get_post($item['variant_id']);
		$variant_id = $item['variant_id'] ? $post->post_title : 'No variant ID';
		$variant_id .= '</br><span style="color: grey; ">/'.$post->post_name.'</span>';
		$variant_id = apply_filters( 'burst_experiment_variant_id', $variant_id );

		$actions = array(
			'edit'   => '<a href="' . admin_url( 'post.php?post=' . $item['variant_id'] ) . '&action=edit">' . __( 'Edit variant post', 'burst' ) . '</a>',
		);
		return $variant_id . $this->row_actions( $actions );
	}


	/**
	 * Retrieve the table columns
	 *
	 * @return array $columns Array of all the list table columns
	 * @since 1.5
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox"/>',
			'title' => __( 'Name', 'burst' ),
			'control_id' => '<span class="burst-experiment-dot control"></span>'. __( 'Control', 'burst' ),
			'variant_id' => '<span class="burst-experiment-dot variant"></span>'. __( 'Variant', 'burst' ),
			'goals' => __( 'Goal', 'burst' ),
			'status' => __( 'Status', 'burst' ),
		);

//not sure what this should do @hessel
//		if ( ! $this->show_default_only ) {
//			$columns['control_id'] = __( 'Control', 'burst' );
//			$columns['variant_id'] = __( 'Variant', 'burst' );
//			$columns['kpi'] = __( 'Goal', 'burst' );
//			$columns['status'] = __( 'Active', 'burst' );
//		}

		return apply_filters( 'burst_experiment_columns', $columns );

	}

	/**
	 * Get the sortable columns
	 *
	 * @return array Array of all the sortable columns
	 * @since 2.1
	 */
	public function get_sortable_columns() {
		$columns = array(
			'title' => array( 'title', true ),
			'status' => array( 'status', true),
		);

		return $columns;
	}



	/**
	 * Retrieve the current page number
	 *
	 * @return int Current page number
	 * @since 1.5
	 */
	public function get_paged() {
		return isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1;
	}


	/**
	 * Retrieve the current status
	 *
	 * @return int Current status
	 * @since 2.1.7
	 */
	public function get_status() {
		return isset( $_GET['status'] ) ? sanitize_title( $_GET['status'] )
			: false;
	}

	/**
	 * Retrieves the search query string
	 *
	 * @return mixed string If search is present, false otherwise
	 * @since 1.7
	 */
	public function get_search() {
		return ! empty( $_GET['s'] ) ? urldecode( trim( $_GET['s'] ) ) : false;
	}

	/**
	 * Build all the reports data
	 *
	 * @return array $reports_data All the data for customer reports
	 * @global object $wpdb Used to query the database using the WordPress
	 *                      Database API
	 * @since 1.5
	 */
	public function reports_data() {

		if ( ! burst_user_can_manage() ) {
			return array();
		}

		$data    = array();
		$paged   = $this->get_paged();
		$offset  = $this->per_page * ( $paged - 1 );
		$search  = $this->get_search();
		$status  = $this->get_status();
		$order   = isset( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';
		$orderby = isset( $_GET['orderby'] )
			? sanitize_text_field( $_GET['orderby'] ) : 'id';

		$args = array(
			'number'  => $this->per_page,
			'offset'  => $offset,
			'order'   => $order,
			'orderby' => $orderby,
		);
		if ($status) $args['status'] = $status;

		$args['title'] = $search;

		$this->args = $args;
		$experiments    = burst_get_experiments( $args );
		if ( $experiments ) {

			foreach ( $experiments as $experiment ) {
				$data[] = array(
					'ID'   => $experiment->ID,
					'title' => $experiment->title,
					'control_id' => $experiment->control_id,
					'variant_id' => $experiment->variant_id,
					'goals' => $experiment->goal,
					'status' => $experiment->status,
				);
			}
		}

		return $data;
	}


	public function prepare_items() {

		$columns  = $this->get_columns();
		$hidden   = array(); // No hidden columns
		$sortable = $this->get_sortable_columns();
        error_log("prepare items");
		$this->process_bulk_action();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->reports_data();

		$this->total = count( burst_get_experiments() );

		// Add condition to be sure we don't divide by zero.
		// If $this->per_page is 0, then set total pages to 1.
		$total_pages = $this->per_page ? ceil( (int) $this->total
		                                       / (int) $this->per_page ) : 1;

		$this->set_pagination_args( array(
			'total_items' => $this->total,
			'per_page'    => $this->per_page,
			'total_pages' => $total_pages,
		) );
	}
}
