<?php
	/**
	*	Seria TV Welcome page
	*/
	require_once(dirname(__FILE__)."/common.php");
	$gui->activeMenuItem('publisher');
        SERIA_Base::pageRequires("javascript");
        SERIA_Base::pageRequires("login");
        SERIA_Base::viewMode("admin");

	$gui->exitButton(_t("Logout"), "location.href='./logout.php';");

        $gui->contentsFrame("../../../");

	echo $gui->output();
