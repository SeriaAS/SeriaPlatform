<?php

if (!isset($_REQUEST['continue']) || !isset($_REQUEST['loginPage']) || !isset($_REQUEST['type']) || !isset($_REQUEST['class']) || !isset($_REQUEST['providerId']))
	return;

$continue = $_REQUEST['continue'];
$loginPage = $_REQUEST['loginPage'];
$loginType = $_REQUEST['type'];
$class = $_REQUEST['class'];
$providerId = $_REQUEST['providerId'];

$loginState = new SERIA_AuthenticationState();
$loginState->set('link:'.$class, true);
$loginState->set('abort', $continue);
$loginPage = new SERIA_Url($loginPage);
SERIA_Authproviders::loadProviders($class);
$provider = SERIA_Authproviders::getProvider($providerId);
$url = SERIA_Authproviders::getProviderUrl($provider, $continue, $loginPage, $loginState);

SERIA_Base::redirectTo($url);
