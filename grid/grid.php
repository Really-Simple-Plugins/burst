<?php
defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

add_action( 'admin_enqueue_scripts', 'burst_enqueue_grid_assets' );
function burst_enqueue_grid_assets( $hook ) {
	if (   
		   $hook !== 'toplevel_page_burst' 
		&& $hook !== 'burst-1_page_burst-insights' 
		&& $hook !== 'burst-1_page_burst-experiments'
	) return;

	wp_register_style( ' burst-muuri',
		trailingslashit( burst_url ) . "grid/css/muuri.css", "",
		burst_version );
	wp_enqueue_style( ' burst-muuri' );

	wp_register_script( ' burst-muuri',
		trailingslashit( burst_url )
		. 'grid/js/muuri.min.js', array( "jquery" ),
		burst_version );
	wp_enqueue_script( ' burst-muuri' );

	wp_register_script( ' burst-grid',
		trailingslashit( burst_url )
		. 'grid/js/grid.js', array( "jquery", " burst-muuri" ),
		burst_version );
	wp_enqueue_script( ' burst-grid' );
}

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

	$template_part = $grid_item['body'].'.php';
	$template_part = burst_get_template($template_part);
	$contents = str_replace( array(
		'{class}',
		'{header}',
		'{controls}',
		'{body}',
		'{index}',
	), array(
		$grid_item['class'],
		$grid_item['title'],
		$grid_item['controls'],
		$grid_item['content'],
		$grid_item['index'],
	), $contents );


	return $contents;
}


