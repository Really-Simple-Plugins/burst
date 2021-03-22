<?php

$items = array(
	1 => array(
		'title' => '<div class="rsp-yellow burst-bullet"></div>',
		'content' => __("Really Simple SSL - Easily migrate your website to SSL"),
		'link' => 'https://wordpress.org/plugins/burst/',
		'class' => 'burst',
		'constant_free' => 'burst_plugin',
		'constant_premium' => 'burst_pro_plugin',
		'website' => 'https://burst.com/pro',
		'search' => 'Really+Simple+SSL+Burst',
	),
	2 => array(
		'title' => '<div class="rsp-blue burst-bullet"></div>',
		'content' => __("Complianz Privacy Suite - Cookie Consent Management as it should be ", "burst"),
		'link' => 'https://wordpress.org/plugins/complianz-gdpr/',
		'class' => 'cmplz',
		'constant_free' => 'cmplz_plugin',
		'constant_premium' => 'cmplz_premium',
		'website' => 'https://complianz.io/pricing',
		'search' => 'complianz',
	),
	3 => array(
		'title' => '<div class="rsp-pink burst-bullet"></div>',
		'content' => __("Zip Recipes - Beautiful recipes optimized for Google ", "burst"),
		'link' => 'https://wordpress.org/plugins/zip-recipes/',
		'class' => 'zip',
		'constant_free' => 'ZRDN_PLUGIN_BASENAME',
		'constant_premium' => 'ZRDN_PREMIUM',
		'website' => 'https://ziprecipes.net/premium/',
		'search' => 'zip+recipes+recipe+maker+really+simple+plugins',
		),
);

$element = burst_get_template('dashboard/our-plugins-row.php');
$output = '';
foreach ($items as $item) {
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
		BURST::$admin->get_status_link($item),
		$item['class'],
		'',
	), $element);
}

echo $output;