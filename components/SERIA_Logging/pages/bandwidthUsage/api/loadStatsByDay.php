<?php
	require_once(dirname(__FILE__).'/../../../../../main.php');
	$namespace = $_GET["namespace"];
	$date = $_GET["date"];
	SERIA_Template::disable();
	$counter = new SERIA_Counter($namespace);

	$daydata = array();
	for($hour=0;$hour<=23;$hour++) {
		$usageInfo = $counter->get(array('b-YmdH:'.$date." ".(strlen($hour)==1?'0'.$hour:$hour)));
		$daydata[(strlen($hour)==1?'0'.$hour:$hour).':00'] = array_pop($usageInfo);
	}
	SERIA_Lib::publishJSON($daydata);
