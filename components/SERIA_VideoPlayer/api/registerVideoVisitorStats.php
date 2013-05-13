<?php

	require_once(dirname(__FILE__)."/../../../main.php");
	SERIA_Template::disable();

	if(isset($_GET['expires']) && time()>intval($_GET['expires'])) die("URL has expired");

	if(!SERIA_Url::current()->isSigned(intval($_REQUEST["vid"]).SERIA_FILES_ROOT.SERIA_DB_PASSWORD))
		die("Invalid signature");

	if($_REQUEST["seenMap"] == '')
		return;

	if(!isset($_REQUEST["vid"]))
		throw new SERIA_Exception("No such video");
	if(!isset($_REQUEST["euid"]))
		throw new SERIA_Exception("Invalid userId");
	if(!isset($_REQUEST["seenMap"]))
		throw new SERIA_Exception("Erroneous SeenMap");

	$vid = SERIA_Meta::load('SERIA_Video', $_REQUEST["vid"]);
	$euid = $_REQUEST["euid"];
	$seenMap = $_REQUEST["seenMap"];

	$res = SERIA_Meta::all('SERIA_VideoVisitorStats')->where("video=:vid", array("vid" => $vid->get("id")))->where("euid=:euid", array("euid" => $euid))->limit(1);

	if($res->count() == 1) {
		$obj = $res->current();
		$currentSeenMap = $obj->get("seenMap");
		$oldLinePos = strpos($currentSeenMap, ",");

		if(!$oldLinePos) {
			// old seen map needs to be converted
			$oldLine = str_split($currentSeenMap);
			$currentSeenMap = implode(",", $oldLine);
		}

		$pos = strpos($seenMap, ",");

		if($pos === false) {
			throw new SERIA_Exception("Discarding seenMap(".$seenMap.") due to lack of data on euid".$euid." and vid=".$vid);
		}
		$oldMap = explode(",", $currentSeenMap); // 0000000011111111111111110000000000000 changed to 0,0,0,0,0,1,1,1,1,1,1,1,,2,2,2,2,2,2,
		$newMap = array();

		foreach(explode(",", $seenMap) as $i => $second) {
			$newMap[$i] = $oldMap[$i] + $second;
		}

		$obj->set("seenMap", implode(",", $newMap));
		SERIA_Meta::save($obj);

		echo json_encode(array("status" => "ok"));

	} else if($res->count() == 0) {
		$obj = new SERIA_VideoVisitorStats();

		$obj->set("video", $vid);
		$obj->set("objectKey", SERIA_NamedObjects::getPublicId(SERIA_Meta::load('SERIA_Video', $vid->get("id"))));
		$obj->set("euid", $euid);
		$obj->set("seenMap", $seenMap);

		SERIA_Meta::save($obj);

		echo json_encode(array("status" => "ok"));
	}
