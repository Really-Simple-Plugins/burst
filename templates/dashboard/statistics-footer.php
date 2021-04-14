<?php
    $default_experiment_id = burst_get_default_experiment_id();
    $experiment = new BURST_EXPERIMENT($default_experiment_id);
    $running = $experiment->status === 'active';
    $hide = 'style="display:none;"';
    ?>

<div class="burst-experiment-stop" <?php echo $running ? $hide : ''?> >
	<button disabled class="button button-primary burst-statistics-action" data-experiment_action="stop"><?php _e("Pause", "burst")?></button>
	<button disabled class="button button-tertiary burst-statistics-action" data-experiment_action="stop"><?php _e("Stop experiment", "burst")?></button>
</div>
<div class="burst-experiment-start" <?php echo $running ? '' : $hide ?>>
	<button disabled class="button button-primary burst-statistics-action" data-experiment_action="start"><?php _e("Start", "burst")?></button>
</div>