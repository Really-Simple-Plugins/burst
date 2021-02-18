<div class="wrap" id="burst">
	<h1 class="burst-noticed-hook-element"></h1>
	<div class="burst-{page}">
		<div id="burst-header">
			<img src="<?php echo trailingslashit(burst_url)?>assets/images/burst-logo.svg">
			<div class="burst-header-right">
                <?php
                $experiments = burst_get_experiments();
                $default_experiment = $experiments;
                $default_experiment = reset($default_experiment);
                ?>
                <select name="burst_selected_experiment_id">
                    <option value=""><?php _e("Select an experiment", "burst")?></option>
                    <?php
                    foreach ($experiments as $experiment){
                        ?>
                        <option value="<?php echo $experiment->ID?>" <?php if ( $default_experiment->ID == $experiment->ID) echo 'selected'?> ><?php echo $experiment->title?></option>
                        <?php
                    }
                    ?>
                </select>
                <a href="https://wpburst.com/support" class="button button-black" target="_blank"><?php _e("Support", "burst") ?></a>
            </div>
		</div>
		<div id="burst-content-area">
			{content}
		</div>
	</div>
</div>