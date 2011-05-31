<?php
	require_once(dirname(__FILE__).'/../../../../main.php');
	$namespace = $_GET["namespace"];
	$fromDate = rawurldecode($_GET["fromDate"]);
	$toDate = rawurldecode($_GET["toDate"]);
	$objectId = $_GET["id"];
//	$labelResolution = $_GET["res"] ? $_GET["res"] : 10; Handteres av urlStats.php ?
	SERIA_Template::disable();
	$counter = new SERIA_Counter($namespace);

	if(($q = strpos($fromDate, " ")) !== false) {
		$timeString = substr($fromDate, $q+1);
		$newTimeString = $timeString.substr('00:00:00', strlen($timeString));
		$fromDate = substr($fromDate, 0,$q)." ".$newTimeString;
	}
	if(($q = strpos($toDate, " ")) !== false) {
		$timeString = substr($toDate, $q+1);
		$newTimeString = $timeString.substr('00:00:00', strlen($timeString));
		$toDate = substr($toDate, 0,$q)." ".$newTimeString;
	}

	$timedifference = strtotime($toDate) - strtotime($fromDate);

	$daySpan = intval($timedifference/intval(60*60*24));
//	$monthSpan = intval($daySpan/30);
	$hourSpan = round($timedifference/(60*60));
	if($daySpan<1) {
		$stats = array();
		for($hours=0;$hours<$hourSpan;$hours++) {
			$usageInfo = $counter->get(array('b-YmdH:'.$date));
			$stats[] = array_pop($usageInfo)+rand(1300,5000);
			$labels[] = date('H', strtotime('+'.$hours.' hours', strtotime($fromDate)));
		}
		// show hours not days
	} else {
		$stats = array();
		for($day=0;$day<=$daySpan;$day++) {
			$usageInfo = $counter->get(array('b-YmdH:'.$date));
			$stats[$day] = array_pop($usageInfo)+$day+rand(15000,18000);
			$labels[$day] = date('Y-m-d', strtotime('+'.$day.' days', strtotime($fromDate)));
		}
	}

	$statistics['data'] = $stats;
	$statistics['labels'] = $labels;
	echo '{"data":'.json_encode($statistics['data']).',"labels":'.json_encode($labels).'}';
//	SERIA_Lib::publishJSON($statistics);
