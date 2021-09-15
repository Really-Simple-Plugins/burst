<div class="burst-wizard-menu">
    <div class="burst-wizard-title">
        {title}
	</div>
<!--    <div class="burst-wizard-progress-bar">-->
<!--        <div class="burst-wizard-progress-bar-value" style="width: {percentage-complete}%"></div>-->
<!--    </div>-->
    <div class="burst-wizard-menu-menus">
        {steps}
    </div>
</div>

<?php $hide = isset( $_POST['burst-save']) ? 'burst-settings-saved--fade-in': ''; ?>
<div class="burst-settings-saved <?php echo $hide?>">
    <div class="burst-settings-saved__text_and_icon">
        <?php echo burst_icon('check', 'success', '', 18); ?>
        <span><?php _e('Changes saved successfully', 'burst') ?> </span>
    </div>
</div>
