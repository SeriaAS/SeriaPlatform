<?php
	$contents = ob_get_clean();
	
	$gui->contents($contents);
	$gui->output();
?>