<?php
require(dirname(__FILE__).'/../../main.php');

SERIA_ProxyServer::noCache();

if ($_SESSION['proxyTestState']) {
	switch ($_SESSION['proxyTestState']) {
		case 'start':
			$_SESSION['proxyTestState'] = mt_rand();
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'/seria/tests/proxydiagnostics/trampoline.php?value='.$_SESSION['proxyTestState']);
			break;
		case 'stop':
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'/seria/tests/proxydiagnostics/ok.php');
			break;
		default:
			die("Proxy-test: Ikke best\303\245tt! Ukorrekt sessiondata!");
	}
} else
	die("Proxy-test: Ikke best\303\245tt! Mangler sessiondata!");