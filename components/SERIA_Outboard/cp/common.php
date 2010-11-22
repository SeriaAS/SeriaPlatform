<?php
	require_once(dirname(__FILE__)."/../../../main.php");
	SERIA_Base::pageRequires('login');
	$gui = new SERIA_Gui(_t('Outboard'));
	$gui->activeMenuItem('controlpanel/outboard');
