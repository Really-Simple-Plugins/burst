<div class="wrap">

	<form id='burst-ab-test-settings' action="" method="post">

		<h3><?php _e( "Your experiment", 'burst' ) ?></h3>
		
		
		<?php wp_nonce_field( 'burst_save_ab_test', 'burst_nonce' ); ?>

		<?php
		if ( ! $id ) { ?>
			<input type="hidden" value="1" name="burst_add_new">
		<?php } ?>
		<?php //some fields for the cookies categories 
		BURST::$field->get_fields( 'BURST_AB_TEST',
						'general' );
		?>
		

		<div class="burst-ab-test-save-button">
			<button class="button button-primary"
			        type="submit"><?php _e( 'Save',
					'burst' ) ?></button>
		</div>

	</form>
</div>	