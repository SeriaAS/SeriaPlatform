<?php
	require_once(dirname(__FILE__)."/../common.php");

	try
	{
		$article = SERIA_Article::createObjectFromId($_GET["id"]);

		if(!$_GET["caption"])
			$res = false;
		else if(!$_GET["time"])
			$res = false;
		else
			$res = $article->addTimingStatistics($_GET["caption"], floatval($_GET["time"]));

		SERIA_Lib::publishJSON($res);
	}
	catch (Exception $e)
	{
		SERIA_Lib::publishJSON(array("error" => $e->getMessage()));
	}
