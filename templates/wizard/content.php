<div class="burst-section-content">

    <form class="burst-form" action="{page_url}" method="POST">
		<input type="hidden" value="{page}" name="wizard_type">
		<input type="hidden" value="{step}" name="step">
		<input type="hidden" value="{section}" name="section">
        <input type="hidden" value="{experiment_id}" name="experiment_id">
<!--        <script>-->
<!--            if ('URLSearchParams' in window) {-->
<!--                var searchParams = new URLSearchParams(window.location.search)-->
<!--                searchParams.set("experiment_id", "{experiment_id}");-->
<!--                var newRelativePathQuery = window.location.pathname + '?' + searchParams.toString();-->
<!--                history.pushState(null, '', newRelativePathQuery);-->
<!--            }-->
<!--        </script>-->
		<?php wp_nonce_field( 'burst_save', 'burst_nonce' ); ?>

        <div class="burst-wizard-title burst-section-content-title-header">{title}</div>
        <div class="burst-wizard-title burst-section-content-notifications-header">
			<?php _e("Notifications", "burst")?>
		</div>
	    {intro}

		{fields}

        <div class="burst-section-footer">
            {previous_button}
            {save_button}
            {next_button}
        </div>

    </form>

</div>

