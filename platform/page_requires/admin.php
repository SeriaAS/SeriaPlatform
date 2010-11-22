<?php
	SERIA_Base::pageRequires('login');

	if(!is_object(SERIA_Base::user()))
	{
		if(defined(SERIA_CUSTOM_PAGES_ROOT) && file_exists(SERIA_CUSTOM_PAGES_ROOT."/login.php"))
		{
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT."/seria/platform/pages/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"]));
			die();
		}			
	}
	if(!SERIA_Base::user()->isAdministrator())
	{
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT."/seria/platform/pages/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"]));
		die();
		SERIA_Base::displayErrorPage('403', _t('Access denied'), _t("This page requires administrator privileges."));
//		throw new SERIA_Exception(_t("This page requires administrator privileges."));
	}
