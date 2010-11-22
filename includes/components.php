<?php
	/**
	*	Include file for supporting Seria Platform components
	*/
	$GLOBALS['seria']['components'] = array();

	$components = glob(SERIA_ROOT."/seria/components/*", GLOB_ONLYDIR);
	$callbacks = array();
	foreach($components as $c)
	{
		require($c."/component.php");
		$bn = basename($c);
		if(function_exists($bn.'_init'))
			$callbacks[] = $bn.'_init';
	}

	foreach($callbacks as $callback)
		$callback();
