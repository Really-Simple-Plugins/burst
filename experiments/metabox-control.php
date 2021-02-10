<?php
$post_id = get_the_ID();
$experiment_id = burst_get_experiment_id_for_post($post_id);

if (intval($experiment_id)) { 
	$experiment = new BURST_EXPERIMENT($experiment_id);
	error_log('experiment');
	error_log(print_r($experiment, true));
	?>

<?php } ?>
	<form action="" method="post">
		<div id='burst-metabox-experiment-settings'> 
			<?php wp_nonce_field( 'burst_create_experiment_nonce', 'burst_nonce' ); ?>
				<!-- Check if post already has experiment -->
			<?php if (intval($experiment_id) && !empty($experiment->variant_id)) { ?>
				<!-- Experiment exists -->
				<div class="burst-experiment-settings-info">
					<h4><?php echo $experiment->title; ?></h4>
					<a href="<?php echo get_permalink($experiment->control_id) ?>" class="control"><span class="burst-experiment-dot control"></span><?php echo get_the_title($experiment->control_id); ?></a>
					<a href="<?php echo get_permalink($experiment->variant_id) ?>" class="variant"><span class="burst-experiment-dot variant"></span><?php echo get_the_title($experiment->variant_id); ?></a>
				</div>

				<input type="hidden" value="<?php echo $experiment->variant_id ?>" name="burst_redirect_to_variant">

				<div class="burst-experiment-save-button">
					<input class="button button-secondary" name="burst_go_to_setup_experiment_button"
					        type="submit" value="<?php _e( 'Edit variant and setup experiment',
							'burst' ) ?>">
				</div>
				<p class="burst-info-box">
					<?php 
						_e( "When you click 'Edit variant and setup experiment', you will be redirected to the page you have selected as your variant.", "burst")
                        .' '.
						_e( "Over there you can continue the setup and start the experiment!", "burst")
						.' '.
                        _e("Happy experimenting!", "burst");
					?>			
				</p>

			<?php } else { ?>

				<!-- Experiment does NOT exist -->

				<input type="hidden" value="1" name="burst_create_experiment">

				<input type="hidden" value="<?php echo $post_id ?>" name="burst_original_post_id">
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT','general' );
				?>
				
				<div class="burst-label">
					<label for="title">
						<span class="burst-experiment-dot control"><?php _e('Control', 'burst'); ?></span>
					</label>
				</div>
				<p class="control"><?php the_title(); ?> <span class="burst-accompanied-text">(<?php _e('Current page' , 'burst'); ?>)</span></p>

				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'create' );
				?>

				<div class="burst-experiment-save-button">
					<input class="button button-primary" name="burst_create_experiment_button"
					        type="submit" value="<?php _e( 'Save and edit variant',
							'burst' ) ?>">
				</div>
				<p class="burst-info-box">
					<?php 
						_e( "When you click 'Save and edit variant', you will be redirected to the page you have selected as your variant.", "burst"); 
						_e( "Over there you can continue the setup and start the experiment! Happy experimenting!", "burst"); 
					?>			
				</p>
			<?php } ?>

		</div>
	</form>