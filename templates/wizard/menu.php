<div class="cmplz-wizard-menu">
    <div class="cmplz-wizard-title">{title}
		<span class="cmplz-save-settings"><?php //echo cmplz_icon('save', 'error');?></span>
		<?php $hide = isset( $_POST['cmplz-save'] ) ? '': 'style="display:none"'; ?>
		<span class="cmplz-settings-saved" <?php echo $hide?>><?php echo cmplz_icon('save', 'success')?></span>
	</div>
    <div class="cmplz-wizard-progress-bar">
        <div class="cmplz-wizard-progress-bar-value" style="width: {percentage-complete}%"></div>
    </div>
    <div class="cmplz-wizard-menu-menus">
        {steps}
    </div>
</div>
