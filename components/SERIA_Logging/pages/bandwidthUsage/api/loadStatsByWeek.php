<?php
	require_once(dirname(__FILE__).'/../../../../../main.php');
	$week = $_GET["week"];
	$year = $_GET["year"];
	SERIA_Template::disable();
	$counter = new SERIA_Counter(SERIA_Logging::BANDWIDTH_NS);

	$weekdata = array();
	for($day=1;$day<=7;$day++) {
		$usageInfo = $counter->get(array('Ymd:'.date('Ymd', strtotime($year."W".$week.$day))));
		$weekdata[$day] = array_pop($usageInfo);
	}

	$week = array(
		'Monday' => $weekdata[1],
		'Tuesday' => $weekdata[2],
		'Wednesday' => $weekdata[3],
		'Thursday' => $weekdata[4],
		'Friday' => $weekdata[5],
		'Saturday' => $weekdata[6],
		'Sunday' => $weekdata[7],
	);

	SERIA_Lib::publishJSON($week);
