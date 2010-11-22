<?php
	require("common.php");

	$gui->title(_t('Installed Applications'));

	$contents = '<h1 class="legend">'._t('Installed Applications').'</h1>';

	// each app should return an array("caption","onclick","icon");
	$icons = SERIA_Applications::hook('apps/controlpanel/applications/installed.php/icons');


	$gui->contents($contents);
	$gui->output();
