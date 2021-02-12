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
					<div class="burst-experiment-settings-info_container control">
						<span class="burst-experiment-dot control"></span>
						<div class="burst-experiment-settings-info_title">
							<p><?php echo get_the_title($experiment->control_id); ?></p>
							<a href="<?php echo get_permalink($experiment->control_id) ?>"><?php _e('View', 'burst') ?></a> | 
							<a href="<?php echo get_edit_post_link($experiment->control_id) ?>"><?php _e('Edit', 'burst') ?></a>
						</div>
					</div>
					<div class="burst-experiment-settings-info_container variant">
						<span class="burst-experiment-dot variant"></span>
						<div class="burst-experiment-settings-info_title">
							<p><?php echo get_the_title($experiment->variant_id); ?></p>
							<a href="<?php echo get_permalink($experiment->variant_id) ?>"><?php _e('View', 'burst') ?></a>
						</div>
					</div>
				</div>
				<!-- <p>Fill in a name and select the type of experiment. Choose which page you want to use as a variant. Then choose the weight of your experiment. When your done click on 'Save and setup variant'. This will take you to the variant page and over there you can change the variant, choose your goal and start experimenting! </p> -->
				<?php wp_nonce_field( 'burst_start_experiment', 'burst_nonce' ); ?>

				<?php	
				if ( ! intval($experiment_id) ) { ?>
					<input type="hidden" value="1" name="burst_create_experiment">
				<?php } ?>

				<input type="hidden" value="<?php echo $post_id ?>" name="burst_original_post_id">
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'goal' );
				?>


				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'timeline' );
				?>
				<div class="burst-experiment-save-button">
					<input class="button button-primary" name="burst_start_experiment_button"
					        type="submit" value="<?php _e( 'Start the experiment',
							'burst' ) ?>">
				</div>
			</div>
	</form>