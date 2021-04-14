<?php
$post_id = isset($_GET['post']) ? intval($_GET['post']) : false;
if (!$post_id ) {
    ?>
    <p><?php _e('Save your post to get the experiment options', 'burst')?></p>
        <?php
} else {

$experiment_id = burst_get_experiment_id_for_post($post_id);

if (intval($experiment_id)) { 
	$experiment = new BURST_EXPERIMENT($experiment_id);
	?>

<?php } ?>
	<form action="" method="post">
		<div id='burst-metabox-experiment-settings'> 
			<?php wp_nonce_field( 'burst_save', 'burst_nonce' ); ?>
				<!-- Check if post already has experiment -->
			<?php if (intval($experiment_id) && !empty($experiment->variant_id)) { ?>
				<!-- Experiment exists -->

				<div class="burst-experiment-settings-info">
					<h4><?php echo $experiment->title; ?></h4>
					<div class="burst-experiment-settings-info_container control">
						<span class="burst-experiment-dot control"></span>
						<div class="burst-experiment-settings-info_title">
							<p><?php echo get_the_title($experiment->control_id); ?></p>
							<a href="<?php echo get_permalink($experiment->control_id) ?>"><?php _e('View', 'burst') ?></a>
						</div>
					</div>
					<div class="burst-experiment-settings-info_container variant">
						<span class="burst-experiment-dot variant"></span>
						<div class="burst-experiment-settings-info_title">
							<p><?php echo get_the_title($experiment->variant_id); ?></p>
							<a href="<?php echo get_permalink($experiment->variant_id) ?>"><?php _e('View', 'burst') ?></a>
							 | 
							<a href="<?php echo get_edit_post_link($experiment->variant_id) ?>"><?php _e('Edit', 'burst') ?></a>
						</div>
					</div>
				</div>

				<input type="hidden" value="<?php echo $experiment->variant_id ?>" name="burst_redirect_to_variant">

				<div class="burst-experiment-save-button">
					<?php
					if ($experiment->status !== 'draft') {
						echo burst_display_experiment_status($experiment->status); 
					}
					?>
					<input class="button button-secondary" name="burst_go_to_setup_experiment_button"
					        type="submit" value="<?php _e( 'Edit variant and setup experiment',
							'burst' ) ?>">
				</div>
				<p class="burst-info-box">
					<?php 
						echo 
						__( "When you click 'Edit variant and setup experiment', you will be redirected to the page you have selected as your variant.", "burst")
                        .'&nbsp;'.
						__( "Over there you can continue the setup and start the experiment!", "burst")
						.'&nbsp;'.
                        __("Happy experimenting!", "burst");
					?>			
				</p>

			<?php } else { ?>

				<!-- Experiment does NOT exist -->
				<input type="hidden" value="1" name="burst_create_experiment">
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
						echo                      
						__( "When you click 'Save and edit variant', you will be redirected to the page you have selected as your variant.", "burst")
						.'&nbsp;'.
						__( "Over there you can continue the setup and start the experiment!", "burst")
						.'&nbsp;'.
                        __("Happy experimenting!", "burst");
					?>			
					?>			
				</p>
			<?php } ?>

		</div>
	</form>
<?php
}