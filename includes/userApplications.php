<?php
	/**
	*	Include file for supporting Seria Platform applications
	*/

	$GLOBALS['seria']['applications'] = array();

	if(defined('SERIA_USER_APPS')) {
		$apps = explode(",", trim(SERIA_USER_APPS));
		foreach($apps as $i => $app) {
			if($app) {
				$apps[$i] = SERIA_ROOT.'/seria/apps/'.$app;
			} else unset($apps[$i]);
		}
	} else {
	        if(!($apps = SERIA_Base::coreCache('user-apps'))) {
			$apps = glob(SERIA_ROOT."/seria/apps/*", GLOB_ONLYDIR);
	                SERIA_Base::coreCache('user-apps', $apps, 5);
	        }
	}
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

	SERIA_Manifests::processManifests('apps', $manifests);
	SERIA_Hooks::dispatch(SERIA_Application::EMBED_HOOK);
