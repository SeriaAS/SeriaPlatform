<?php
	/**
	*	Seria TV Welcome page
	*/
	require_once(dirname(__FILE__)."/common.php");
	$gui->activeMenuItem('controlpanel/users/edit');
        SERIA_Base::pageRequires("javascript");
        SERIA_Base::pageRequires("login");
        SERIA_Base::viewMode("admin");

	if(isset($_GET["id"]))
		$user = SERIA_User::createObject($_GET["id"]);
	else
		$user = SERIA_User::createObject();

	$contents ="<h1 class='legend'>".($user->get("id") ? _t("Edit user").": ".$user->get("display_name") : _t("Create new user"))."</h1>";

	$form = new SERIA_UserEditForm($user);
	if($form->receive($_POST))
	{
		header("Location: ".SERIA_HTTP_ROOT."/seria/apps/controlpanel/components/usermanagement/");
		die();
	}
	$contents .= $form->output();

	$gui->contents($contents);
	
	echo $gui->output();
	
	
