<div class="wrap" id="burst">
	<h1 class="burst-noticed-hook-element"></h1>
	<div class="burst-{page}">
		<div id="burst-header">
			<img src="<?php echo trailingslashit(burst_url)?>assets/images/burst-logo.svg">
			<div class="burst-header-right">
                <a href="https://wpburst.com/docs/" class="link-black" target="_blank"><?php _e("Documentation", "burst")?></a>
                <a href="https://wpburst.com/support" class="button button-black" target="_blank"><?php echo _e("Support", "burst") ?></a>
            </div>
		</div>
		<div id="burst-content-area">
			{content}
		</div>
	</div>
</div>