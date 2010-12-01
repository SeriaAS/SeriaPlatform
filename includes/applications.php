<?php
	/**
	*	Include file for supporting Seria Platform applications
	*/

	$GLOBALS['seria']['applications'] = array();

	$apps = glob(SERIA_ROOT."/seria/apps/*", GLOB_ONLYDIR);
	foreach($apps as $app)
		require($app."/application.php");

	$manifests = array();
	foreach($apps as $app)
	{
		$bn = basename($app);
		$func = $bn.'Init';
		if(function_exists($func))
			$func();

		if(class_exists($bn."Manifest", false))
		{
			$manifests[] = $bn."Manifest";
		}
	}

	SERIA_Base::processManifests('apps', $manifests);

//	SERIA_Hooks::dispatch('platform_applications_loaded', $GLOBALS["seria"]["applications"]);

	/**
	*	DEPRECATED
	*
	*	Execute embed() for each application, allowing the application
	*	to integrate with the user interface properly.
	*/
//	foreach(SERIA_Applications::getApplications() as $app)
//		$app->embed();

	SERIA_Hooks::dispatch(SERIA_Application::EMBED_HOOK);


//	SERIA_Hooks::dispatch('platform_applications_embedded', $GLOBALS["seria"]["applications"]);
