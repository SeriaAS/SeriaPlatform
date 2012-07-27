<?php

require(dirname(__FILE__).'/../../../main.php');

if (isset($_GET['continue']))
	$continue = $_GET['continue'];
else
	$continue = SERIA_HTTP_ROOT;

SERIA_ProxyServer::noCache();

$logoutUrl = SAPI_ExtauthUser::logoutUrl($continue);

SERIA_Base::redirectTo($logoutUrl);
