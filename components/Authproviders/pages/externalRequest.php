<?php

require_once(dirname(__FILE__).'/../../../main.php');

if (!isset($_GET['token']))
	throw new SERIA_Exception('No external login token presented.');
if (!isset($_GET['from']))
	throw new SERIA_Exception('No external login return url presented.');

$component = SERIA_Components::getComponent('seria_authproviders');

if (isset($_GET['interactive']) && $_GET['interactive'] == 'no') {
	SERIA_Base::debug('External login request is not interactive!');
	if (SERIA_Base::user() === false) {
		/*
		 * Don't attempt to show a login form in auto-mode. Also drop discovery cookie.
		 */
		SERIA_ExternalAuthenticationAgent::setStatus($_GET['token'], SERIA_ExternalAuthenticationAgent::STATUS_FAILED);
		$component->setLoginDiscoveryCookie('logout');
		$url = $_GET['from'];
		$url = str_replace(array("\n", "\r", "\0"), array('', '', ''), $url);

		if (strpos($url, '?') !== false)
			$url .= '&return='.urlencode($url).'&failure=logout';
		else
			$url .= '?return='.urlencode($url).'&failure=logout';
		SERIA_Base::redirectTo($url);
		die();
	}
} else {
	if (!isset($_GET['guest']) || $_GET['guest'] != 'yes')
		$action = SERIA_AuthproviderActions::getLoginAction();
	else
		$action = SERIA_AuthproviderActions::getGuestLoginAction();
	if ($action) {
		/*
		 * Authentication state entry point (ASEP)
		 */
		$state = new SERIA_AuthenticationState();
		$state->set('continue', SERIA_Url::current()->__toString());

		SERIA_Base::redirectTo($action->__toString());
		die();
	}
}

/*
 * Logged in.. return to remote.
 */

$code = SERIA_ExternalAuthenticationAgent::getCode($_GET['token']);
SERIA_ExternalAuthenticationAgent::setUid($_GET['token'], SERIA_Base::user()->get('id'));
if (SERIA_Base::hasSystemAccess())
	SERIA_ExternalAuthenticationAgent::setStatus($_GET['token'], SERIA_ExternalAuthenticationAgent::STATUS_OK);
else
	SERIA_ExternalAuthenticationAgent::setStatus($_GET['token'], SERIA_ExternalAuthenticationAgent::STATUS_GUEST);

$url = $_GET['from'];
$url = str_replace(array("\n", "\r", "\0"), array('', '', ''), $url);

if (strpos($url, '?') !== false)
	$url .= '&return='.urlencode($url).'&code='.urlencode($code);
else
	$url .= '?return='.urlencode($url).'&code='.urlencode($code);
SERIA_Base::redirectTo($url);
die();
