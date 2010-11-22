<?php

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_Base::pageRequires('logout');

if (isset($_GET['from'])) {
	$url = $_GET['from'];
	$url = str_replace(array("\n", "\r", "\0"), array('', '', ''), $url);
	SERIA_Base::redirectTo($url);
	die();
}

if (isset($_GET['continue'])) {
	$url = $_GET['continue'];
	$url = str_replace(array("\n", "\r", "\0"), array('', '', ''), $url);
	header('Location: '.$url);
	die();
}
