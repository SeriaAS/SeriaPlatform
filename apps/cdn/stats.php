<?php
	require_once('common.php');
	$gui->title('CDN Statistics');
	$gui->activeMenuItem('cdn/statistics');
	$gui->contents('<h1 class="legend">'._t("Statistics").'</h1><p>Statistics have not been completed yet.</p>');
	echo $gui->output();
