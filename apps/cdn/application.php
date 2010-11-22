<?php
	/**
	*	Add local classes to the class path
	*/
	if(!file_exists(SERIA_ROOT.'/_config.cdn.php'))
		return;
	require_once(SERIA_ROOT.'/_config.cdn.php');

	if(!defined('SERIACDN_HOST'))
	{
		$pi = new SERIA_Url(SERIA_HTTP_ROOT);
		define('SERIACDN_HOST', $pi->getHost());
	}

	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/cdn/classes/*.class.php");

	/**
	*	Register this application
	*/
	$seriaCDN = new SERIA_CDNApplication();
	SERIA_Applications::addApplication($seriaCDN);
	SERIA_Hooks::listen('seria_router', array($seriaCDN, 'router'), 1000);
	SERIA_Hooks::listen('SERIA_UserEditForm', array($seriaCDN, 'userEdit'));

