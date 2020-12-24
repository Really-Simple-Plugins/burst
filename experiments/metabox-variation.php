<?php
if (burst_post_has_experiment()) {
?>
	
<h1>Burst has experiment</h1>
<?php
} else {
?>
	
	<form action="" method="post">
		<div id='burst-metabox-experiment-settings'> 

				<?php wp_nonce_field( 'burst_create_experiment', 'burst_nonce' ); ?>

				<?php
				$post_id = get_the_ID();
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
					        type="submit" value="<?php _e( 'Configure variant',
							'burst' ) ?>">
				</div>
			</div>
		</div>
	</form>
<?php
}