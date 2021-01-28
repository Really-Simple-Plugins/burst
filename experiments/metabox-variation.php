<?php
$post_id = get_the_ID();
$experiment_id = burst_get_experiment_id_for_post($post_id);

if (intval($experiment_id)) { 
	$experiment = new BURST_EXPERIMENT($experiment_id);
	error_log(print_r($experiment));
	?>

<?php } else { ?>
	<form action="" method="post">
		<div id='burst-metabox-experiment-settings'> 

				<?php wp_nonce_field( 'burst_create_experiment', 'burst_nonce' ); ?>

				<?php
				
				$experiment_id = 0;
				if ( ! $experiment_id ) { ?>
					<input type="hidden" value="1" name="burst_create_experiment">
				<?php } ?>

				<input type="hidden" value="<?php echo $post_id ?>" name="burst_original_post_id">
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'general' );
				?>
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'weight' );
				?>

				<div class="burst-experiment-save-button">
					<input class="button button-secondary" name="burst_create_experiment_button"
					        type="submit" value="<?php _e( 'Save',
							'burst' ) ?>">
					<input class="button button-primary" name="burst_create_experiment_button"
					        type="submit" value="<?php _e( 'Save and configure variant',
							'burst' ) ?>">
				</div>
			</div>
		</div>
	</form>
<?php }