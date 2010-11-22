<?php
	/**
	*	Add local classes to the class path
	*/
	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/translation/classes/*.class.php");

	/**
	*	Register this application
	*/
	SERIA_Applications::addApplication($seriaTranslation = new SERIA_TranslationApplication());
	SERIA_Hooks::listen(SERIA_Application::EMBED_HOOK, array($seriaTranslation, 'embed'));
