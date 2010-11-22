<?php
	require_once(dirname(__FILE__)."/main.php");
	SERIA_Base::user(NULL); /* Logout */
	header('Location: '.SERIA_HTTP_ROOT);
	die();
