<div class="burst-wizard-conclusion">
    <h3>{title}</h3>
    <h1><?php _e('Start your experiment', 'burst') ?></h1>
    <h2><?php _e('Versus', 'burst') ?></h2>
    <div class="burst-wizard-conclusion__summary">
        <div class="burst-wizard-conclusion__summary__control">
            <?php burst_display_experiment_version('control'); ?>
            <h3>{control_title}</h3>
            <p>
                <i>{control_url}</i>
            </p>
            <p><a href="{control_edit_url}"><?php _e('Edit', 'burst') ?></a></p>
        </div>
        <div class="burst-wizard-conclusion__summary__variant">
            <?php burst_display_experiment_version('variant'); ?>
            <h3>{variant_title}</h3>
            <p>
                <i>{variant_url}</i>
            </p>
            <p><a href="{variant_edit_url}"><?php _e('Edit', 'burst') ?></a></p>
        </div>
    </div>
    <h2><?php _e('Experiment settings', 'burst') ?></h2>
    <div class="burst-wizard-conclusion__settings">
        <p class="burst-wizard-conclusion__settings__key"><b>Goal:</b></p>
        <p class="burst-wizard-conclusion__settings__value">{goal}</p>

        <p class="burst-wizard-conclusion__settings__key"><b>Significance:</b></p>
        <p class="burst-wizard-conclusion__settings__value">{significance}%</p>
    </div>
</div>