<?php
	require(dirname(__FILE__)."/../../../main.php");


	if(SERIA_Base::isLoggedIn())
	{
		try
		{
	
			if(!isset($_GET['article_id']))
				throw new Exception('articleId not set');
	
			$article = SERIA_Article::createObjectFromId($_GET['article_id']);
	
			$article->recordEvent($_GET['event_type'], intval($_GET['event_value']), intval($_GET['event_timestamp']));
			SERIA_Template::override('text/xml', '<success />');

			die();
		} 
		catch (Exception $e)
		{
			SERIA_Template::override('text/xml','<error>'.$e->getMessage().'</error>');
		}
	} else {
		SERIA_Template::override('text/xml', '<error>not_logged_in</error>');
	}
