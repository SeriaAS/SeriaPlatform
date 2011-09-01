<?php

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_ProxyServer::noCache();

SERIA_Hooks::dispatchToFirst('guestLogin');
