<?php
	// do not cache in admin mode
	require_once(dirname(__FILE__)."/../../../seria/main.php");
	SERIA_Base::pageRequires('login');
	$gui = new SERIA_GUI(_t('Seria ESI Frontend'));
