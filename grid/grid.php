<?php
defined( 'ABSPATH' ) or die();

function burst_grid_container($content){
	$file = trailingslashit(burst_path) . 'grid/templates/grid-container.php';

	if (strpos($file, '.php') !== false) {
		ob_start();
		require $file;
		$contents = ob_get_clean();
	} else {
		$contents = file_get_contents($file);
	}

	return str_replace('{content}', $content, $contents);
}

function burst_grid_element($grid_item){
	$file = trailingslashit(burst_path) . 'grid/templates/grid-element.php';

	if (strpos($file, '.php') !== false) {
		ob_start();
		require $file;
		$contents = ob_get_clean();
	} else {
		$contents = file_get_contents($file);
	}

	// Controls
	if ( ! $grid_item['controls'] ) {
		$controls = apply_filters('burst_controls_'.$grid_item['body'], $grid_item['controls']);
	} else {
		$controls = $grid_item['controls'];
	}

	// Body
	if ( ! $grid_item['body'] ) {
		$body = $grid_item['page'] . '/' . $grid_item['type'] . '.php';
		$body = burst_get_template($body);
	} else {
		$body = $grid_item['body'];
	}

	// Footer
	$template_part_footer = $grid_item['page'].'/'.$grid_item['type'].'-footer.php';
	$template_part_footer = burst_get_template($template_part_footer);
	if ($template_part_footer) {
		$footer = $template_part_footer;
	} else {
		$template_part_footer = $grid_item['page'].'/footer.php';
		$footer = burst_get_template($template_part_footer, array('footer' => '') );
	}


	$contents = str_replace( array(
		'{class}',
		'{header}',
		'{controls}',
		'{body}',
		'{index}',
		'{type}',
		'{footer}',

	), array(
		$grid_item['class'],
		$grid_item['title'],
		$controls,
		$body,
		$grid_item['index'],
		$grid_item['type'],
		$footer,

	), $contents );


	return $contents;
}


/**
 * Load a grid block
 */
function burst_load_grid_block(){
	$error = false;
	$html = '';

	if ( ! burst_user_can_manage() ) {
		$error = true;
	}

	if ( !isset($_GET['experiment_id'])) {
		$error = true;
	}

	if ( !isset($_GET['type'])) {
		$error = true;
	}

	if ( !$error ) {
		$type = sanitize_title($_GET['type']);
		$html = burst_get_template('dashboard/'.$type.'.php');
	}

	$return  = array(
		'success'  => !$error,
		'html'     => $html,
	);
	echo json_encode( $return );
	die;
}
add_action( 'wp_ajax_burst_load_grid_block', 'burst_load_grid_block');



