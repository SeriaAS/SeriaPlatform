<?php
	/**
	*	Add local classes to the class path
	*/
	if(!file_exists(SERIA_ROOT.'/_config.live.php'))
		return;
	require_once(SERIA_ROOT.'/_config.live.php');


	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/live/classes/*.class.php");

	/**
	*	Register this application
	*/
	$seriaLive = new SERIA_LiveApplication();
	SERIA_Applications::addApplication($seriaLive);

//        SERIA_Hooks::listen(SERIA_Application::EMBED_HOOK, array($seriaPublisher, 'embed'));

	SERIA_Hooks::listen(SERIA_Application::EMBED_HOOK, array($seriaLive, 'embed'));
	//SERIA_Hooks::listen('seria_router', array($seriaLive, 'embed'));
	SERIA_Hooks::listen('SERIA_UserEditForm', 'live_user_edit');

	function live_user_edit($form, $user)
	{
		if(!$user->isAdministrator())
		{
			$form->subForm('LiveRights', new SERIA_LiveRightsForm($user));
		}
	}
