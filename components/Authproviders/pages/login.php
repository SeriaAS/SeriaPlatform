<?php

require(dirname(__FILE__).'/../../../main.php');

if (isset($_GET['continue']))
	$continue = $_GET['continue'];
else
	$continue = SERIA_HTTP_ROOT;

SERIA_Base::redirectTo(SAPI_ExtauthUser::loginUrl($continue));
