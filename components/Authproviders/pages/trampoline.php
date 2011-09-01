<?php

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_ProxyServer::noCache();

$state = new SERIA_AuthenticationState();

$state->terminate('continue');
