<?php

require_once(dirname(__FILE__).'/../../main.php');
SERIA_Base::setErrorHandlerMode('exception');

$return = array(
	'filename' => ''
);

if (isset($_REQUEST['fileArticleId']) && $_REQUEST['fileArticleId']) {
	$fileArticle = SERIA_Article::createObjectFromId($_REQUEST['fileArticleId']);
	$return['filename'] = $fileArticle->get('title');
}

SERIA_Lib::publishJSON($return);
