<?php
	require('../common.php');

	$gui->activeMenuItem('serialive/presentations');
	$gui->title(_t("Presentations"));

	$contents = '<h1 class="legend">'._t("Presentations").'</h1>';

	echo $gui->contents($contents)->output();
