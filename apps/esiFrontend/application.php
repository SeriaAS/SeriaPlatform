<?php
	if(!file_exists(dirname(__FILE__).'/../../../_config.esiFrontend.php'))
		return;
	require(dirname(__FILE__).'/../../../_config.esiFrontend.php');

	/**
	*	Add local classes to the class path
	*/
	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/esiFrontend/classes/*.class.php");

	/**
	*	Register this application
	*/
	SERIA_Applications::addApplication($seriaEsiFrontend = new SERIA_EsiFrontendApplication());
	SERIA_Hooks::listen(SERIA_Application::EMBED_HOOK, array($seriaEsiFrontend, 'embed'));
