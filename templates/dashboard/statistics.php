<?php defined( 'ABSPATH' ) or die( "you do not have access to this page!" );
    $experiment_id = BURST::$experimenting->get_selected_experiment_id();
    $experiment = new BURST_EXPERIMENT($experiment_id);

    $experiment_start = $experiment->date_started;
    $experiment_end = $experiment->date_end;
    ?>
    <input type="hidden" name="burst_experiment_start" value="<?php echo $experiment_start?>">
    <input type="hidden" name="burst_experiment_end" value="<?php echo $experiment_end?>">
    <input type="hidden" name="burst_experiment_id" value="<?php echo $experiment->id ?>">
    <div class="burst-statistics-container">
        <canvas class="burst-chartjs-stats"></canvas>
    </div>


