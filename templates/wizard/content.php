<div class="burst-section-content">

    <form class="burst-form" action="{page_url}" method="POST">
		<input type="hidden" value="{page}" name="wizard_type">
		<input type="hidden" value="{step}" name="step">
		<input type="hidden" value="{section}" name="section">
		<?php wp_nonce_field( 'burst_save', 'burst_nonce' ); ?>

        <div class="burst-wizard-title burst-section-content-title-header">{title}</div>
        <div class="burst-wizard-title burst-section-content-notifications-header">
			<?php _e("Notifications", "burst")?>
		</div>
	    {learn_notice}
	    {intro}
		{post_id}

		{fields}

        <div class="burst-section-footer">
            {save_as_notice}
            <div class="burst-buttons-container">
                {previous_button}
                {save_button}
                {next_button}
            </div>
        </div>

    </form>

</div>

