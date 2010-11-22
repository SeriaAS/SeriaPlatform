<?php
	// do not cache in admin mode
	require_once(dirname(__FILE__)."/../../../seria/main.php");
	SERIA_Base::pageRequires("login");
	$gui = new SERIA_Gui(_t("Seria Platform Control Panel"));
	$gui->activeMenuItem('controlpanel');

	$application = SERIA_Applications::getApplication('seria_controlpanel');

/* Old menu
	if(SERIA_Base::isAdministrator()) {
		$gui->topMenu(_t('Settings'), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/controlpanel/application_settings/\";", "settings");
	}
*/

/*
	if(SERIA_Base::isAdministrator()) {
		$gui->topMenu(_t("Applications"), "location.href=\"" . SERIA_HTTP_ROOT . "/seria/apps/controlpanel/applications/\";", "applications");
	}
	
	if (SERIA_Base::isAdministrator()) {
		$gui->topMenu(_t('System Status'), 'location.href="' . SERIA_HTTP_ROOT . '/seria/apps/controlpanel/status/";', 'status');
	}
*/
	
