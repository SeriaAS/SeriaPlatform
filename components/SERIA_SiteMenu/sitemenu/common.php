<?php
	SERIA_Base::pageRequires("admin");

	$gui = new SERIA_Gui(_t('Site menu'));

	$optionsMenu = new SERIA_GUI_SectionMenu($gui, _t('Options'));
	
	ob_start();	
?>