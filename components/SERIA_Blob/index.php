<?php
	require('common.php');
	ob_start();
?>
	<h1 class="legend"><?php echo _t("File management"); ?></h1>
	<p><?php echo _t("Seria Platform provides a flexible management system for all files uploaded by users on this website. Files are stored in persistent locations on your web server, but can also be rerouted to other servers by using clever scripts for synchronizing off-site hosted files."); ?></p>
<?php
	$contents = ob_get_contents();
	ob_end_clean();
	echo $gui->contents($contents)->output();
