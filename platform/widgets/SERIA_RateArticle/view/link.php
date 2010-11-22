<?php

if (count($_POST) > 0) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	SERIA_Template::disable();
	$widget = SERIA_Widget::createObject($_POST['widgetid']);
	$article = $widget->getNamedObject();
	$article->vote(intval($_POST['rating']));
	SERIA_Lib::publishJSON(array($_POST['widgetid'], $_POST['rating']));
	die();
}

SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_RateArticle/view/rate.js.php');
SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_RateArticle/view/style.css');

$frameId = 'SERIA_RateArticle_form_'.mt_rand();
echo seria_bml('a', array('href' => '#', 'onclick' => 'SERIA_RateArticle.linkClick(\''.$frameId.'\', '.$this->getId().'); return false;'))->setText('Rate')->output();
echo seria_bml('div', array('class' => 'SERIA_RateArticle', 'id' => $frameId))->output();

?>