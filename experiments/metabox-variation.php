<form id='burst-experiment-settings' action="" method="post">
	<div class="burst-grid">
		<div class="burst-grid grid-active" data-id="1" data-table_type="{type}" data-default_range="week">

			
			<h3><?php _e( "Experiment", 'burst' ) ?></h3>
			
			
			<?php wp_nonce_field( 'burst_save_experiment', 'burst_nonce' ); ?>

			<?php
			if ( ! $id ) { ?>
				<input type="hidden" value="1" name="burst_add_new">
			<?php } ?>
			<?php
			BURST::$field->get_fields( 'BURST_EXPERIMENT',
							'general' );

			?><h3><?php _e( "Goals", 'burst' ) ?></h3><?php 
			BURST::$field->get_fields( 'BURST_EXPERIMENT',
							'goals' );

			?><h3><?php _e( "Start your experiment", 'burst' ) ?></h3><?php 
			BURST::$field->get_fields( 'BURST_EXPERIMENT',
							'start_experiment' );

			?><h3><?php _e( "Timeline", 'burst' ) ?></h3><?php 
			BURST::$field->get_fields( 'BURST_EXPERIMENT',
							'timeline' );

			?>

			<div class="burst-experiment-save-button">
				<button class="button button-secondary"
				        type="submit"><?php _e( 'Save',
						'burst' ) ?></button>
	
				
			</div>
		</div>	
	</div>
</form>