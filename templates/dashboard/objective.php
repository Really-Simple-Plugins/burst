<?php defined( 'ABSPATH' ) or die();?>
<?php
    /**
     * file is loaded with ajax, where experiment id is posted.
     */
    $experiment_id = BURST::$experimenting->get_selected_experiment_id();
    $experiment = new BURST_EXPERIMENT($experiment_id);
    $args = array(
        'test_version' => 'control',
    );
    $count_control_all = $experiment->count_hits($args);
    $args['converted'] =  true;
    $count_control_completed = $experiment->count_hits($args);

    $args = array(
        'test_version' => 'variant',
    );
    $count_variant_all = $experiment->count_hits($args);
    $args['converted'] =  true;
    $count_variant_completed = $experiment->count_hits($args);
    $total = $count_control_all + $count_variant_all;

    if ($count_control_all==0) {
	    $percentage = 0;
    } else {
	    $percentage = $experiment->probability_of_control_winning();
    }
?>
<div class="burst-progress-bar-container">
	<div class="burst-progress">
		<div class="burst-percentage" style="width:<?php echo $percentage ?>%"></div>
	</div>
</div>
<div class="burst-percentage-text-container">
    <div class="burst-percentage-number"><?php echo $percentage?>%</div>
    <div class="burst-percentage-text"><?php _e("Probability of original winning.","burst")?></div>
</div>
<div class="burst-objective-total-container">
    <div class="burst-objective-text"><?php _e("Total","burst")?></div>
    <div class="burst-objective-number"><?php echo $total?></div>
</div>

<div class="burst-objective-bullets-container">
    <div class="burst-column-1"><div class="burst-experiment-dot control"></div></div>
    <div class="burst-column-2">
        <?php _e("Control","burst")?>
        <?php echo $count_control_completed?>/<?php echo $count_control_all?>
    </div>
    <div class="burst-column-3"><div class="burst-experiment-dot variant"></div></div>
    <div class="burst-column-4">
	    <?php _e("Variant","burst")?>
	    <?php echo $count_variant_completed?>/<?php echo $count_variant_all?>
    </div>
</div>
<div class="burst-divider"></div>
<div class="burst-objective-total-container">
    <div class="burst-objective-text"><?php _e("Probability of improvement","burst")?></div>
</div>

<div class="burst-objective-bullets-container">
    <div class="burst-column-1"><div class="burst-experiment-dot control"></div></div>
    <div class="burst-column-2">
		<?php _e("Control","burst")?>
	    <?php echo $experiment->probability_of_improvement()?>%
    </div>
    <div class="burst-column-3"><div class="burst-experiment-dot variant"></div></div>
    <div class="burst-column-4">
		<?php _e("Variant","burst")?>
		<?php _e("Baseline", "burst")?>
    </div>
</div>



