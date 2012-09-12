<?php

	if (SERIA_Base::isLoggedIn() && !SERIA_Base::hasSystemAccess())
		SERIA_Base::pageRequires('logout');
	if(SERIA_Base::user() === false)
	{
		if(SERIA_CUSTOM_PAGES_ROOT && file_exists(SERIA_CUSTOM_PAGES_ROOT."/login.php"))
		{
			SERIA_ProxyServer::noCache();
			SERIA_Base::redirectTo(SERIA_CUSTOM_PAGES_HTTP_ROOT."/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"]));
			die();
		}
		SERIA_ProxyServer::noCache();
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT."/seria/platform/pages/login.php?continue=".rawurlencode($_SERVER["REQUEST_URI"]));
		die();
	}
