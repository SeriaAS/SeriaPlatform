<?php
	require_once(dirname(__FILE__)."/../common.php");

	try
	{
		$article = SERIA_Article::createObjectFromId($_GET["id"]);

		if(SERIA_Base::viewMode()=="public" && !$article->get("is_published"))
			throw new SERIA_Exception("Article is not published");

		SERIA_Lib::publishJSON($article->toArray());
	}
	catch (Exception $e)
	{
		SERIA_Lib::publishJSON(array("error" => $e->getMessage()));
	}
