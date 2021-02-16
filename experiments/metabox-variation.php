<?php
$post_id = get_the_ID();
$experiment_id = burst_get_experiment_id_for_post($post_id);

if (intval($experiment_id)) { 
	$experiment = new BURST_EXPERIMENT($experiment_id);
	error_log('experiment in metabox');
	error_log(print_r($experiment, true));
	?>

<?php } ?>
	<form action="" method="post">
		<div id='burst-metabox-experiment-settings'>
				<div class="burst-experiment-settings-info">
					<h4><?php echo $experiment->title; ?></h4>
					<p class="control"><span class="burst-experiment-dot control"></span><?php echo get_the_title($experiment->control_id); ?></p>
					<p class="variant"><span class="burst-experiment-dot variant"></span><?php echo get_the_title($experiment->variant_id); ?></p>
				</div>
				<?php wp_nonce_field( 'burst_start_experiment', 'burst_nonce' ); ?>

				<?php	
				if ( ! intval($experiment_id) ) { ?>
					<input type="hidden" value="1" name="burst_create_experiment">
				<?php } ?>
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT', 'goal' );
				?>
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT', 'timeline' );
				?>
				<div class="burst-experiment-save-button">
                    <?php if ($experiment->status !== 'active') { ?>
                        <input class="button button-primary" name="burst_start_experiment_button"
					        type="submit" value="<?php _e( 'Start the experiment', 'burst' ) ?>">
                    <?php } else { ?>
                        <input class="button button-primary" name="burst_stop_experiment_button"
					        type="submit" value="<?php _e( 'Stop the experiment', 'burst' ) ?>">
                    <?php } ?>
				</div>
			</div>
	</form>