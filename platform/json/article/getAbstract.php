<?php
	require_once(dirname(__FILE__)."/../common.php");

	try
	{
		$article = SERIA_Article::createObjectFromId($_GET["id"]);

		SERIA_Lib::publishJSON($article->getAbstract());
	}
	catch (Exception $e)
	{
		SERIA_Lib::publishJSON(array("error" => $e->getMessage()));
	}
