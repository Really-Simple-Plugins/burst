<form action="" method="post">
	<div id='burst-metabox-experiment-settings'> 

			<?php wp_nonce_field( 'burst_save_experiment', 'burst_nonce' ); ?>

			<?php
			$id = 0;
			if ( ! $id ) { ?>
				<input type="hidden" value="1" name="burst_add_new">
			<?php } ?>
			<?php
			BURST::$field->get_fields( 'BURST_EXPERIMENT',
							'general' );
			?>
			<?php
			BURST::$field->get_fields( 'BURST_EXPERIMENT',
							'weight' );
			?>

			<div class="burst-experiment-save-button">
				<button class="button button-secondary"
				        type="submit"><?php _e( 'Save',
						'burst' ) ?></button>
			</div>
		</div>
	</div>
</form>