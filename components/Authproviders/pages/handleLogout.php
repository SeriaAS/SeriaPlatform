<?php

require_once(dirname(__FILE__).'/../classes/SERIA_Authproviders.class.php');

SERIA_Authproviders::disableAutomaticDiscovery();

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_ProxyServer::noCache();

$state = new SERIA_AuthenticationState();

$component = SERIA_Components::getComponent('seria_authproviders');
$component->loggedInByProvider(null);

$state->terminate('continue');
