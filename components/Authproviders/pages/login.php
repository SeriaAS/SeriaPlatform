<?php

require(dirname(__FILE__).'/../../../main.php');

$vars = $_GET;
if (isset($vars['continue'])) {
	$continue = $vars['continue'];
	unset($vars['continue']);
} else
	$continue = SERIA_HTTP_ROOT;

$url = new SERIA_Url(SAPI_ExtauthUser::loginUrl($continue));

foreach ($vars as $name => $value)
	$url->setParam($name, $value);

SERIA_Base::redirectTo($url->__toString());
