<div class="burst-wizard-menu">
    <div class="burst-wizard-title">{title}
		<span class="burst-save-settings"></span>
		<?php $hide = isset( $_POST['burst-save'] ) ? '': 'style="display:none"'; ?>
		<span class="burst-settings-saved" <?php echo $hide?>><?php echo burst_icon('save', 'success')?></span>
	</div>
    <div class="burst-wizard-progress-bar">
        <div class="burst-wizard-progress-bar-value" style="width: {percentage-complete}%"></div>
    </div>
    <div class="burst-wizard-menu-menus">
        {steps}
    </div>
</div>
