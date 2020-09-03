<div class="wrap">

	<form id='burst-ab-test-settings' action="" method="post">
		
		<?php wp_nonce_field( 'complianz_save_cookiebanner', 'cmplz_nonce' ); ?>

		<?php
		if ( ! $id ) { ?>
			<input type="hidden" value="1" name="cmplz_add_new">
		<?php } ?>
		<?php //some fields for the cookies categories ?>
		

		<div class="burst-ab-test-save-button">
			<button class="button button-primary"
			        type="submit"><?php _e( 'Save',
					'burst-ab-test' ) ?></button>
		</div>

	</form>
</div>	