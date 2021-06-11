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
$is_significant = $experiment->is_statistical_significant();
$significance = $experiment->get_significance();
$significance = $significance ? 100 * (1-$significance) : 0;

$margin_of_error = $experiment->get_margin_of_error();
?>
<div class="burst-objective-total-container">
    <div class="burst-objective-text"><?php _e("Significance","burst")?></div>
    <div class="burst-objective-number"><?php echo $significance?>%&nbsp;<?php echo $is_significant ? __("(Significant)", "burst") : __("(Not significant)", "burst");?></div>
</div>

<div class="burst-objective-total-container">
    <div class="burst-objective-text"><?php _e("Margin of error","burst")?></div>
    <div class="burst-objective-number"><?php echo $margin_of_error?>%</div>
</div>

<div class="burst-objective-total-container">
    <div class="burst-objective-text"><?php _e("Sample size reached","burst")?></div>
    <div class="burst-objective-number">
        <?php if ( $experiment->has_reached_minimum_sample_size() ) {
		    _e("Yes","burst");
	    } else {
		    _e("No","burst");
	    } ?></div>
</div>
<div class="burst-divider"></div>

