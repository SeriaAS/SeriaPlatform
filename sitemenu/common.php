<?php
	require_once(dirname(__FILE__)."/../common.php");
	SERIA_Base::pageRequires("admin");
	$gui->setActiveTopMenu("sitemenu");

	$optionsMenu = new SERIA_GUI_SectionMenu($gui, _t('Options'));
	
	ob_start();	
?>