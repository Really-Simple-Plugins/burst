<div class="wrap" id="burst">
	<h1 class="burst-noticed-hook-element"></h1>
	<div class="burst-{page}">
		<div id="burst-header">
			<img src="<?php echo trailingslashit(burst_url)?>assets/images/burst-logo.svg">
            <div class="burst-header-left">
                <div class="burst-header-menu">
                    <?php
                    $burst_menus = $GLOBALS[ 'submenu' ][ 'burst' ];
                    foreach($burst_menus as $menu){
                        $page = $menu[2];
                        $title = $menu[3];
                        $url = admin_url('admin.php?page=' . $page);
                        echo '<a href='. $url .'>'. $title .'</a>';
                    }
                    ?>
                </div>
            </div>
			<div class="burst-header-right">
                <?php
                $experiments = burst_get_experiments(
                	array(
	                	'status'  =>  array(
					        'completed',
					        'active',
					    ),
	                )
                );
                $selected_experiment_id = BURST::$experimenting->get_selected_experiment_id();
                if (isset($_GET['page']) && $_GET['page'] === 'burst') { ?>
                	<div class="burst_selected_experiment_id_wrapper">
                		<label><?php _e('Show experiment data for:', 'burst') ?></label>
	                    <select name="burst_selected_experiment_id">
	                        <option value=""><?php _e("Select an experiment", "burst")?></option>
			                <?php
			                foreach ($experiments as $experiment){
				                ?>
	                            <option value="<?php echo $experiment->ID?>" <?php if ( $selected_experiment_id == $experiment->ID) echo 'selected'?> ><?php echo $experiment->title?></option>
				                <?php
			                }
			                ?>
	                    </select>
	                </div>
                <?php }
                ?>

                <a href="https://wpburst.com/support" class="button button-black" target="_blank"><?php _e("Support", "burst") ?></a>
            </div>
		</div>
		<div id="burst-content-area">
			{content}
		</div>
	</div>
</div>