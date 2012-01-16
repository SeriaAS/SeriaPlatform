<?php
require(dirname(__FILE__).'/../../main.php');

SERIA_ProxyServer::noCache();

if ($_GET['value'] != $_SESSION['proxyTestState']) {
	die("Proxy-test: Ikke best\303\245tt! Omdirigering / datafeil!");
}

$_SESSION['proxyTestState'] = 'stop';
SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'/seria/tests/proxydiagnostics/run.php');