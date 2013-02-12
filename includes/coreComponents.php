<?php
	/**
	*	Include file for supporting Seria Platform components
	*/
	$GLOBALS['seria']['components'] = array();

	if(!($components = SERIA_Base::coreCache('platform-components'))) {
		$components = glob(SERIA_ROOT."/seria/platform/components/*", GLOB_ONLYDIR);
		SERIA_Base::coreCache('platform-components', $components, 10);
	}
	$callbacks = array();
	$manifests = array();
	foreach($components as $c)
	{
		require($c."/component.php");
		$bn = basename($c);
		if(function_exists($bn.'Init'))
		{
			$callbacks[] = $bn.'Init';
		}
		$manifests[] = $bn."Manifest";
	}

	SERIA_Manifests::processManifests('coreComponents', $manifests);

	foreach($callbacks as $callback)
		$callback();
