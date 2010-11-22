<?php
	/**
	*	Seria TV Welcome page
	*/
	require_once(dirname(__FILE__)."/common.php");
	$gui->activeMenuItem('controlpanel/users/list');

	$contents = "<h1 class='legend'>"._t("User Management")."</h1><p>"._t("Seria Platform User Management allows you to create, edit and deactivate users that can access this site.")."</p>";

	$users = SERIA_User::getUsers()->order('display_name');

	$grid = new SERIA_FluentGrid($users);
	$contents .= $grid->output(array('display_name' => 200, 'email'), 'seria_userGridCallback', 20);

	function seria_userGridCallback($object)
	{
		return '<tr><td><a href="user.php?id='.$object->getKey().'">'.$object->get('display_name').'</a></td><td><a href="mailto:'.$object->get('email').'">'.$object->get("email").'</a></td></tr>';
	}

	$gui->contents($contents);
	
	echo $gui->output();
	
	
