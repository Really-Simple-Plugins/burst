<?php

$items = array(
    1 => array(
        'content' => __("Writing Content for Google", "burst"),
        'link'    => 'https://wpburst.com/writing-content-for-google/',
    ),
    2 => array(
        'content' => __("WP Search Insights Beginner's Guide", "burst"),
        'link' => 'https://wpburst.com/burst-beginners-guide/',
    ),
    3 => array(
        'content' => __("Using CSV/Excel Exports", "burst"),
        'link' => 'https://wpburst.com/using-csv-excel-exports/',
    ),
    4 => array(
        'content' => __("Improving your Search Result Page", "burst"),
        'link' => 'https://wpburst.com/improving-your-search-result-page/',
    ),
    5 => array(
        'content' => __("The Search Filter", "burst"),
        'link' => 'https://wpburst.com/the-search-filter/',
    ),
    6 => array(
        'content' => __("Positioning your search form", "burst"),
        'link' => 'https://wpburst.com/about-search-forms/',
    ),
);
$container = '<div class="burst-tips-tricks-element"><a href="{link}" target="_blank"><div class="burst-bullet"></div>{content}<div class="burst-tips-tricks-content"></div></a></div>';
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
echo $output;


