<?php
	require(dirname(__FILE__).'/../../main.php');
	SERIA_Base::pageRequires('admin');
	$gui = new SERIA_Gui(_t("Files management"));
	$gui->activeMenuItem('controlpanel/settings/files');
