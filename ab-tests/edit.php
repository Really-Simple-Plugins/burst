<div class="wrap">

	<form id='burst-ab-test-settings' action="" method="post">

		<h3><?php _e( "Experiment", 'burst' ) ?></h3>
		
		
		<?php wp_nonce_field( 'burst_save_ab_test', 'burst_nonce' ); ?>

		<?php
		if ( ! $id ) { ?>
			<input type="hidden" value="1" name="burst_add_new">
		<?php } ?>
		<?php
		BURST::$field->get_fields( 'BURST_AB_TEST',
						'general' );

		?><h3><?php _e( "Goals", 'burst' ) ?></h3><?php 
		BURST::$field->get_fields( 'BURST_AB_TEST',
						'goals' );

		?><h3><?php _e( "Start your experiment", 'burst' ) ?></h3><?php 
		BURST::$field->get_fields( 'BURST_AB_TEST',
						'start_experiment' );
		?>
		

		<div class="burst-ab-test-save-button">
			<button class="button button-secondary"
			        type="submit"><?php _e( 'Save',
					'burst' ) ?></button>
			<button class="button button-primary"
			        type="submit"><?php _e( 'Save and start the experiment',
					'burst' ) ?></button>
			
		</div>

	</form>
</div>	