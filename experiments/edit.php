<form id='burst-experiment-settings' action="" method="post">
	<div class="burst-grid">
		<div class="burst-grid grid-active" data-id="1" data-table_type="{type}" data-default_range="week">
			<?php

			if (!burst_user_can_manage()) return;			
		    include( dirname( __FILE__ ) . "/metabox-control.php" );
		    include( dirname( __FILE__ ) . "/metabox-variant.php" );

			?>
		</div>	
	</div>
</form>