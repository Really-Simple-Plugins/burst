<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
$this->fields = $this->fields + array(

    'title' => array(
        'step'     => STEP_SELECT,
        'section'  => 1,
        'source'      => 'experiment',
        'type'        => 'text',
        'label'       => __( "Experiment name", 'burst' ),
        'placeholder' => __( 'For example: Red vs green buttons' ),
        'help'        => __( 'This name is for internal use only. Try to give the experiment a clear name, so you can find this test again.', 'burst' ),
        'required' => true,
    ),

        'control_id' => array(
            'step'     => STEP_SELECT,
            'section'  => 1,
            'source'      => 'experiment',
            'type'               => 'select_control',
            'query_settings'	 => array(
                'post_type' 	=> 'any', //burst_get_current_post_type();
                'post_status' 	=> burst_get_all_post_statuses( array('publish') ),
                //'post__not_in' 	=> array( burst_get_current_post_id() ),
            ),
            'help'               => __( 'Select the control page. The control page is the page you want to improve (or compare with another page).',
                'burst' ),
            'required' => true,
        ),

    'variant_id' => array(
        'step'     => STEP_SELECT,
        'section'  => 1,
        'source'      => 'experiment',
        'type'               => 'select_variant',
        'query_settings'	 => array(
            'post_type' 	=> 'any', //burst_get_current_post_type();
            'post_status' 	=> burst_get_all_post_statuses( array('publish') ),
            //'post__not_in' 	=> array( burst_get_current_post_id() ),
        ),
        'help'               => __( 'Select the control page. The control page is the page you want to improve (or compare with another page).',
            'burst' ),
        'required' => true,
    ),

    'goal' => array(
        'step'     => STEP_METRICS,
        'section'  => 1,
        'source'      => 'experiment',
        'type'        => 'radio',
        'options' => array(
            'click'  => __( "Click on element", 'burst' ),
            'visit'  => __( "Page visit", 'burst' ),
        ),
        'label'       => __( "Goal", 'burst' ),
        'default' => 'click-on-element',
        'help'        => __( 'Select what metric you want to improve. For example a click on a button or a visit on a checkout page.', 'burst' ),
        'required' => true,
    ),

    'goal_identifier' => array(
        'step'     => STEP_METRICS,
        'section'  => 1,
        'source'      => 'experiment',
        'type'               => 'text',
        'placeholder' => __( '.class or #id' ),
        'condition' => array(
            'goal' => 'click',
        ),
        'required' => true,
    ),

    'goal_id' => array(
        'step'     => STEP_METRICS,
        'section'  => 1,
        'source'      => 'experiment',
        'type'               => 'select2',
        'query_settings'	 => array(
            'post_type' 	=> 'any', //get_current_post_type();
            'post_status' 	=> 'publish',
        ),
        'condition' => array(
            'goal' => 'visit',
        ),
        'required' => true,
    ),

    'minimum_samplesize' => array(
        'step'     => STEP_METRICS,
        'section'  => 2,
        'source'      => 'experiment',
        'type'               => 'radio',
        'default'            => 384,
        'options' => array(
            '384'  => sprintf(__( "%s visits", 'burst' ), 384),
            '1000'  => sprintf(__( "%s visits", 'burst' ), 100),
            '5000'  => sprintf(__( "%s visits", 'burst' ), 5000),
            '-1'  => __( "Custom number of visits", 'burst' ),
        ),
        'label'       => __( "Timeline", 'burst' ),
        'required' => true,
    ),

    'minimum_samplesize_custom' => array(
        'step'     => STEP_METRICS,
        'section'  => 2,
        'source'      => 'experiment',
        'type'               => 'number',
        'minimum'            => 384,
        'condition' => array(
            'minimum_samplesize' => -1,
        ),
        'required' => true,
    ),



     'percentage_included' => array(
         'step'     => STEP_START,
         'section'  => 1,
         'source'      => 'experiment',
     	'type'        => 'weightslider',
     	'default'	  => '100',
     	'label'       => __( "Experiment weight", 'burst' ),
     	'placeholder' => __( 'Percentage in numbers' ),
     	'help'        => __( 'For internal use only', 'burst' ),
         'required' => true,
     ),

);

