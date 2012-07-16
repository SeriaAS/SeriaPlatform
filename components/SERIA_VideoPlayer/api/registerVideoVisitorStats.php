<?php

	require_once(dirname(__FILE__)."/../../../main.php");
	SERIA_Template::disable();

	if(isset($_GET['expires']) && time()>intval($_GET['expires'])) die("URL has expired");

	if(!SERIA_Url::current()->isSigned(intval($_REQUEST["vid"]).SERIA_FILES_ROOT.SERIA_DB_PASSWORD))
		die("Invalid signature");


	if(!isset($_REQUEST["vid"]))
		throw new SERIA_Exception("No such video");
	if(!isset($_REQUEST["euid"]))
		throw new SERIA_Exception("Invalid userId");
	if(!isset($_REQUEST["seenMap"]))
		throw new SERIA_Exception("Erroneous SeenMap");

	$vid = SERIA_Meta::load('SERIA_Video', $_REQUEST["vid"]);
	$euid = $_REQUEST["euid"];
	$seenMap = $_REQUEST["seenMap"];

	$res = SERIA_Meta::all('SERIA_VideoVisitorStats')->where("video=:vid", array("vid" => $vid->get("id")))->where("euid=:euid", array("euid" => $euid));

	if($res->count() == 1) {
		$obj = $res->current();
		$oldMap = $obj->get("seenMap");
		$newMap = $oldMap | $seenMap;
		$obj->set("seenMap", $newMap);
		SERIA_Meta::save($obj);

		echo json_encode(array("status" => "ok"));

	} else if($res->count() == 0) {
		$obj = new SERIA_VideoVisitorStats();

		$obj->set("video", $vid);
		$obj->set("euid", $euid);
		$obj->set("seenMap", $seenMap);

		SERIA_Meta::save($obj);

		echo json_encode(array("status" => "ok"));
	}
