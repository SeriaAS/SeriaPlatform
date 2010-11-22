<?php

/**
 * Invoke an authprovider. Get parameters:
 *   providerClass: provider class
 *   provider: provider-id
 *   continue: url (optional)
 *   login: login-url (optional)
 */

require_once(dirname(__FILE__).'/../../../main.php');

/* $provider, $continueUrl=false, $loginPage=false, $loginState=false */

if (!isset($_GET['providerClass']) || !isset($_GET['provider']))
	throw new SERIA_Exception('Expected a provider to call');

/*
 * Authentication state entry point (ASEP)
 */
$state = new SERIA_AuthenticationState();

SERIA_Authproviders::loadProviders($_GET['providerClass']);
$provider = SERIA_Authproviders::getProvider($_GET['provider']);

if (!$provider)
	throw new SERIA_NotFoundException('Provider not found!');

if (isset($_GET['continue']))
	$continueUrl = $_GET['continue'];
else
	$continueUrl = false;

if (isset($_GET['login']))
	$loginPage = $_GET['login'];
else
	$loginPage = false;

if ($loginPage === false)
	$loginPage = SERIA_HTTP_ROOT.'/seria/platform/pages/login.php';
$loginPage = new SERIA_Url($loginPage);
if (!$continueUrl)
	$continueUrl = SERIA_Url::current()->__toString();

if (!$state->exists('continue'))
	$state->set('continue', $continueUrl);

$state->set('callProvider', $provider->getProviderId());

$loginPage->setParam('continue', $state->stampUrl(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/trampoline.php'));
$loginPage->setParam('provider', $provider->getProviderId());

SERIA_Base::redirectTo($state->stampUrl($loginPage)->__toString());
die();
