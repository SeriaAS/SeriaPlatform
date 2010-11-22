<?php

require_once(dirname(__FILE__).'/../../../main.php');

if (isset($_GET['key']) && isset($_SESSION['simplesaml_redirect']) && isset($_SESSION['simplesaml_redirect'][$_GET['key']])) {
	$url = $_SESSION['simplesaml_redirect'][$_GET['key']];
	unset($_SESSION['simplesaml_redirect'][$_GET['key']]);
	if (SERIA_AuthenticationState::available()) {
		$state = new SERIA_AuthenticationState();
		$url = $state->stampUrl($url);
	}
	SERIA_Base::redirectTo($url);
	die();
}

SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
die();
