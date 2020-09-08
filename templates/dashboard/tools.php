<?php defined( 'ABSPATH' ) or die( "you do not have access to this page!" );?>


	<ul>
		<?php do_action( 'burst_tools' ) ?>

		<?php if ( class_exists( 'WooCommerce' ) ) { ?>
			<li><i class="fas fa-plus"></i><a
					href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=account' ) ?>"><?php _e( "Manage shop privacy",
						'burst' ); ?></a>
			</li>
		<?php } ?>

		<li>
			<i class="fas fa-plus"></i><?php echo sprintf( __( "For the most common issues see the Complianz %sknowledge base%s",
				'burst' ),
				'<a target="_blank" href="https://wpburst.com/support">',
				'</a>' ); ?>
		</li>
		<li>
			<i class="fas fa-plus"></i><?php echo sprintf( __( "Ask your questions on the %sWordPress forum%s",
				'burst' ),
				'<a target="_blank" href="https://wordpress.org/support/plugin/burst">',
				'</a>' ); ?>
		</li>
	</ul>

