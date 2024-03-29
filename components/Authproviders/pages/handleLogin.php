<?php

require_once(dirname(__FILE__).'/../classes/SERIA_Authproviders.class.php');

SERIA_Authproviders::disableAutomaticDiscovery();

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_ProxyServer::noCache();

$state = new SERIA_AuthenticationState();

if (SERIA_Base::user() !== false)
	SERIA_Base::user(NULL);

/*
 * If the login failed.
 */
if (isset($_GET['failure']) && $_GET['failure']) {
	$state->terminate('abort');
	die();
}

/*
 * If we have lost the state of this login.
 */
if (!$state->exists('guestLogin')) {
	$state->terminate('abort');
	die();
}

if (!$state->get('guestLogin'))
	$loginHandler = 'handleLogin';
else
	$loginHandler = 'handleGuestLogin';

call_user_func(array('SERIA_Authproviders', $loginHandler), $state->get('interactive'));

$state->terminate('continue');
die();
