<?php defined( 'ABSPATH' ) or die();?>
<?php
/**
 * file is loaded with ajax, where experiment id is posted.
 */
    $experiment_id = intval( $_GET['experiment_id'] );
    $experiment = new BURST_EXPERIMENT($experiment_id);
?>
<div class="burst-progress-bar-container">
	<div class="burst-progress">
		<div class="burst-percentage" style="width:30%"></div>
	</div>
</div>