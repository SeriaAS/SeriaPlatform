<?php
	require_once(dirname(__FILE__)."/main.php");
	SERIA_Base::user(NULL); /* Logout */
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
	die();
