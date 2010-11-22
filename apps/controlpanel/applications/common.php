<?php
	require("../common.php");
	$gui->setActiveTopMenu("applications");

	$gui->subMenu(_t('Installed applications'), 'location.href="./installed.php";', 'installed');
	$gui->subMenu(_t('Application Store'), 'location.href="./store.php";', 'store');
