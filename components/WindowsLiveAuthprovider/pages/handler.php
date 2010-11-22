<?php

require_once(dirname(__FILE__).'/../../../main.php');

$provider =& WindowsLiveAuthprovider::getProvider();

$provider->authenticationHandler();
