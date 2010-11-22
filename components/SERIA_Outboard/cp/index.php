<?php
	require('common.php');

	$gui->activeMenuItem('controlpanel/outboard');


	$contents = '<h1 class="legend">'._t("Outboard").'</h1>
<p>'._t("Manage user generated content and other interactive features for your users.").'</p>';

	$gui->contents($contents);

	echo $gui->output();
