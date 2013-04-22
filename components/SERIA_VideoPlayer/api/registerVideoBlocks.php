<?php

	require_once(dirname(__FILE__)."/../../../main.php");
	SERIA_Template::disable();

	if(isset($_GET['expires']) && time()>intval($_GET['expires'])) die("URL has expired");

	if(!SERIA_Url::current()->isSigned(intval($_REQUEST["vid"]).SERIA_FILES_ROOT.SERIA_DB_PASSWORD))
		die("Invalid signature");


	if(!isset($_REQUEST["vid"]))
		throw new SERIA_Exception("No such video");
	if(!isset($_REQUEST["anonymousId"]))
		throw new SERIA_Exception("Erroneous anonymous ID");
	if(!isset($_REQUEST["anonymousSeenMap"]))
		throw new SERIA_Exception("Erroneous seenmap");

	$vid = SERIA_Meta::load('SERIA_Video', $_REQUEST["vid"]);

	// Store everything in a temporary thingie, like a SERIA_Cache
	$anonStat = SERIA_Meta::all('SERIA_AnonymousVideoVisitorStats')->where('uid=:uid', array('uid' => $_REQUEST["anonymousId"]));
	if($anonStat->count() === 1) {
		$statLine = $anonStat->current();
		$newSeenMap = $statLine->get("seenMap") | $_REQUEST["anonymousSeenMap"];
	} else {
		$statLine = new SERIA_AnonymousVideoVisitorStats();
		$newSeenMap = $_REQUEST["anonymousSeenMap"];
	}

	$statLine->set("seenMap", $newSeenMap);
	$statLine->set("uid", $_REQUEST["anonymousId"]);
	$statLine->set("video", $vid);

	SERIA_Meta::save($statLine);
/*

	$percentSeen = $_REQUEST["percentSeen"];

	if($percentSeen<=20) {
		$percentSeen = "0-20";
	} else if($percentSeen>20 && $percentSeen<=40) {
		$percentSeen = "20-40";
	} else if($percentSeen>40 && $percentSeen<=60) {
		$percentSeen = "40-60";
	} else if($percentSeen>60 && $percentSeen<=60) {
		$percentSeen = "60-80";
	} else if($percentSeen>80) {
		$percentSeen = "80-100";
	}

	$counter = new SERIA_Counter("SeriaWebTVStatistics");

	$counter->add(array(
			'PS-Ym/i:'.date('Ym', time()).'/'.$vid->get("id").":".$percentSeen,
			'PS-Ymd/i:'.date('Ymd', time()).'/'.$vid->get("id").":".$percentSeen,
			'PS-YmdH/i:'.date('YmdH', time()).'/'.$vid->get("id").":".$percentSeen,
	));
*/
	echo json_encode(array("status" => "ok"));
