<?php
    defined( 'ABSPATH' ) or die();

    $experiment_id = BURST::$experimenting->get_selected_experiment_id();
    $experiment = new BURST_EXPERIMENT($experiment_id);
    $experiment_start = $experiment->date_started;
    $experiment_end = intval($experiment->date_end)==0 ? time() : $experiment->date_end;
    $notices = BURST::$notices->get_notices(array('cache'=>false));
    $has_notices = count($notices)>0;
    reset($notices);
?>
    <style>
        #burst .burst-statistics-container {
            height:300px !important;
        }
    </style>
    <input type="hidden" name="burst_experiment_start" value="<?php echo $experiment_start?>">
    <input type="hidden" name="burst_experiment_end" value="<?php echo $experiment_end?>">
    <input type="hidden" name="burst_experiment_id" value="<?php echo $experiment->id ?>">
    <div class="burst-notice-container">
        <?php if ($has_notices) BURST::$notices->render_warning($notices)?>
    </div>
    <div class="burst-statistics-container">
        <canvas class="burst-chartjs-stats"></canvas>
    </div>


