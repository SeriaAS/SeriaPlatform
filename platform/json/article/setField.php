<?php
	//todo: check access for article
	require_once(dirname(__FILE__)."/../common.php");

	try
	{
		$article = SERIA_Article::createObjectFromId($_GET["id"]);
		$article->writable();

		$article->set($_GET["fieldName"], $_GET["value"]);
		$article->save();

		SERIA_Lib::publishJSON(true);
	}
	catch (Exception $e)
	{
		SERIA_Lib::publishJSON(array("error" => $e->getMessage()));
	}
