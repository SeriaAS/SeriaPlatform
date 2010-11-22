<?php

require(dirname(__FILE__).'/form_inc.php');

if (count($_POST) && count($errors) == 0) {
	$uri = $_SERVER['REQUEST_URI'];
	if (substr($uri, 0, 1) == '/')
		$uri = substr($uri, 1);
	header('Location: '.SERIA_HTTP_ROOT.$uri);
	die();
}

?>