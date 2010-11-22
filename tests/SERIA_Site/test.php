<?php
	require_once("../../main.php");

	$site = SERIA_Fluent::all('SERIA_Site')->where('domain=:domain', 'default')->current();
var_dump($site);
die("OK");

/*
	$site = new SERIA_Site();
	$site->set('created_date', time());
	$site->set('created_by', SERIA_Fluent::createObject('SERIA_User', 1));
	$site->set('domain', 'default');
	$site->set('title', 'Default site');
	$site->save();
*/
