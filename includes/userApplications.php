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
	SERIA_Hooks::dispatch(SERIA_Application::EMBED_HOOK);
