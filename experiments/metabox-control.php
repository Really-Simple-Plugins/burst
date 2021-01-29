<?php
$post_id = get_the_ID();
$experiment_id = burst_get_experiment_id_for_post($post_id);

if (intval($experiment_id)) { 
	$experiment = new BURST_EXPERIMENT($experiment_id);
	error_log(print_r($experiment, true));
	?>

<?php } ?>
	<form action="" method="post">
		<div id='burst-metabox-experiment-settings'> 
				<!-- <p>Fill in a name and select the type of experiment. Choose which page you want to use as a variant. Then choose the weight of your experiment. When your done click on 'Save and setup variant'. This will take you to the variant page and over there you can change the variant, choose your goal and start experimenting! </p> -->
				<?php wp_nonce_field( 'burst_create_experiment', 'burst_nonce' ); ?>

				<?php
				
				if ( ! intval($experiment_id) ) { ?>
					<input type="hidden" value="1" name="burst_create_experiment">
				<?php } ?>

				<input type="hidden" value="<?php echo $post_id ?>" name="burst_original_post_id">
				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'general' );
				?>
				
				<div class="burst-label">
					<label for="title">
						<span class="burst-experiment-dot control">Control</span>
					</label>
					
				</div>
				<p class="control"><?php the_title(); ?> <span class="burst-accompanied-text">(Current page)</span></p>

				<?php
				BURST::$field->get_fields( 'BURST_EXPERIMENT',
								'create' );
				?>

				<div class="burst-experiment-save-button">
					<input class="button button-primary" name="burst_create_experiment"
					        type="submit" value="<?php _e( 'Save and edit variant',
							'burst' ) ?>">
				</div>
				<p class="burst-info-box">When you click 'Save and edit variant', you will be redirected to the page you have selected as your variant. Over there you can continue the setup and start the experiment! Happy experimenting!</p>
			</div>
		</div>
	</form>