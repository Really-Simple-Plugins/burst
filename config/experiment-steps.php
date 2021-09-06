<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );
$this->steps = array(
	'experiment' =>
		array(
            STEP_SELECT => array(
				"id"    => "company",
				"title" => __( "General", 'burst' ),
				'sections' => array(
					1 => array(
				    'title' => __('Visitors', 'burst'),
				    'intro' => '<p>'. _x('The Burst Wizard will guide you through the necessary steps to configure your website for privacy legislation around the world. We designed the wizard to be comprehensible, without making concessions in legal compliance.','intro first step', 'burst') .'</p>' .
				               _x('There are a few things to assist you during configuration:','intro first step', 'burst') .'<ul>'.
				               '<li>' . _x('Hover over the question mark behind certain questions for more information.', 'intro first step', 'burst').'</li>' .
		                   '<li>' . _x('Important notices and relevant articles are shown in the right column.', 'intro first step', 'burst').'</li>' .
		                   '<li>' . sprintf(_x('Our %sinstructions manual%s contains more detailed background information about every section and question in the wizard.','intro first step', 'burst'),'<a target="_blank" href="https://wpburst.com/manual">', '</a>') .'</li>' .
		                   '<li>' . sprintf(_x('You can always %slog a support ticket%s if you need further assistance.','intro first step', 'burst'),'<a target="_blank" href="https://wordpress.org/support/plugin/burst/">', '</a>') .'</li></ul>',

			    ),
					2 => array(
						'id'    => 'general',
						'title' => __( 'Documents', 'burst' ),
						'intro' => '<p>'._x('Here you can select which legal documents you want to generate with Burst. You can also use existing legal documents.', 'intro company info', 'burst').'</p>',
					),
					3 => array(
						'id' => 'impressum_info',
						'title' => __( 'Website information',
							'burst' ),
						'intro' => '<p>'._x( 'We need some information to be able to generate your documents.',
							'intro company info', 'burst' ).'</p>',
					),
					4 => array(
						'id' => 'impressum_info',
						'title' => __('Impressum', 'burst'),
						'region' => array('eu'),
					),
					6 => array(
						'title' => __( 'Purpose', 'burst' ),
					),
					8 => array(
						'region' => array( 'us' ),
						'id'     => 'details_per_purpose_us',
						'title'  => __( 'Details per purpose',
							'burst' ),
					),
					11 => array(
						'title' => __('Security & Consent', 'burst'),
					),
				),
			),

			STEP_METRICS => array(
				"title"    => __( "Cookies", 'burst' ),
				"id"       => "cookies",
				'sections' => array(
					1 => array(
						'title' => __( 'Cookie scan', 'burst' ),
						'intro' =>
                            '<p>'.__( 'Burst will scan several pages of your website for first-party cookies and known third-party scripts. The scan will be recurring monthly to keep you up-to-date!', 'burst' ) . '&nbsp;' .
                                  sprintf( __( 'For more information, %sread our 5 tips%s about the cookie scan.', 'burst'), '<a href="https://wpburst.com/cookie-scan-results/" target="_blank">','</a>').'</p>',
					),
					2 => array(
						'title' => __( 'Statistics', 'burst' ),
						'intro' => '<p>'._x( 'Below you can choose to implement your statistics tooling with Burst. We will add the needed snippets and control consent at the same time.',
							'intro statistics', 'burst' ) .burst_read_more("https://wpburst.com/statistics-implementation") .'</p>'
					),
					3 => array(
						'title' => __( 'Statistics - configuration', 'burst' ),
							'intro' => '<p>'._x( 'If you choose Burst to handle your statistics implementation, please delete the current implementation.',
								'intro statistics configuration', 'burst' ) .burst_read_more("https://wpburst.com/statistics-implementation#configuration") .'</p>'
					),
					4 => array(
						'title' => __( 'Integrations', 'burst' ),
					),

					5 => array(
						'title' => __( 'Cookie descriptions', 'burst' ),
						'intro' => '<p>'
						           .__( 'Burst provides your Cookie Policy with comprehensive cookie descriptions, supplied by cookiedatabase.org.','burst')
						           ."&nbsp;"
						           . __('We connect to this open-source database using an external API, which sends the results of the cookiescan (a list of found cookies, used plugins and your domain) to cookiedatabase.org, for the sole purpose of providing you with accurate descriptions and keeping them up-to-date at a weekly schedule.','burst')
					                .burst_read_more("https://wpburst.com/our-cookiedatabase-a-new-initiative/")
						           .'</p>',

					),
					6 => array(
						'title' => __( 'Service descriptions', 'burst' ),
						'intro' => '<p>'._x( 'Below services use cookies on your website to add functionality. You can use cookiedatabase.org to synchronize information or edit the service if needed. Unknown services will be moderated and added by cookiedatabase.org as soon as possible.',
							'intro used cookies', 'burst' ).'</p>'
					),


				),
			),
			STEP_START    => array(
				"id"    => "menu",
				"title" => __( "Documents", 'burst' ),
				'intro' =>
					'<h1>' . _x( "Get ready to finish your configuration.",
						'intro menu', 'burst' ) . '</h1>' .
					'<p>'
					. _x( "Generate your documents, then you can add them to your menu directly or do it manually after the wizard is finished.",
						'intro menu', 'burst' ) . '</p>',
				'sections' => array(
					1 => array(
						'title' => __( 'Create documents', 'burst' ),
					),
					2 => array(
						'title' => __( 'Link to menu', 'burst' ),
					),
				),

			),
		),
);
