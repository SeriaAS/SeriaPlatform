<?php
	$publisherApplicationEnabled = file_exists(dirname(__FILE__).'/../../../_config.publisher.php');
	if (!$publisherApplicationEnabled)
		return;
	require(dirname(__FILE__).'/../../../_config.publisher.php');

	/**
	*	Add local classes to the class path
	*/
	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/publisher/classes/*.class.php");
	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/publisher/classes/sitemenu/*.class.php");

	/**
	*	Register this application
	*/
	function publisherInit()
	{
		global $publisherApplicationEnabled;
		if (!$publisherApplicationEnabled)
			return;
		SERIA_Applications::addApplication($seriaPublisher = new SERIA_PublisherApplication());
		SERIA_Hooks::listen(SERIA_Application::EMBED_HOOK, array($seriaPublisher, 'embed'));
		SERIA_Hooks::listen('SERIA_UserEditForm', 'publisher_user_edit');
	}

	function publisher_user_edit($form, $user)
	{
		if(!$user->isAdministrator())
			$form->subForm('PublisherRights', new SERIA_PublisherRightsForm($user), -1000);
	}
