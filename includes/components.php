<?php
	/**
	*	Include file for supporting Seria Platform components
	*/
	$GLOBALS['seria']['components'] = array();

	$components = glob(SERIA_ROOT."/seria/components/*", GLOB_ONLYDIR);
	shuffle($components);
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
		else if(function_exists($bn.'_init'))
		{
			SERIA_Base::debug("<strong>"._t("Component '%component%' is using deprecated callback '%wrong_callback%' to initialize. Rename function to '%callback%'.", array(
				'component' => $c,
				'wrong_callback' => $bn.'_init()',
				'callback' => $bn.'Init()',
			))."</strong>");
			$callbacks[] = $bn.'_init';
		}

		if(class_exists($bn."Manifest", false))
		{
			$manifests[] = $bn."Manifest";
		}
	}

	SERIA_Base::processManifests('components', $manifests);

	foreach($callbacks as $callback)
		$callback();
