<?php

if (count($_POST) > 0) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	SERIA_Template::disable();
	$widget = SERIA_Widget::createObject($_POST['widgetid']);
	if ($widget->getFrom())
		$emailHeaders = 'From: '.$widget->getFrom();
	$subject = _t('Article recommendation');
	$message = _t('A user has recommended this article: %URL%', array('URL' => $widget->assertGetURL()))."\n\n".
	           _t('Name of user (as provided by web interface): %NAME%', array('NAME' => $_POST['name']))."\n\n".
	           SERIA_HTTP_ROOT;
	$error = false;
	try {
		if (($error = SERIA_IsInvalid::name($_POST['name'], true)))
			throw new Exception(_t('Name: ').$error);
		if (($error = SERIA_IsInvalid::email($_POST['email'], true)))
			throw new Exception(_t('E-Mail: ').$error);
		mail($_POST['email'], $subject, $message, $emailHeaders);
	} catch (Exception $e) {
		$error = $e->getMessage();
	}
	if ($error === false)
		SERIA_Lib::publishJSON(array($_POST['widgetid'], $_POST['name'], $_POST['email']));
	else
		SERIA_Lib::publishJSON(array('error' => $error));
	die();
}

SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_RecommendByEMail/view/recommend.js.php');
SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_RecommendByEMail/view/style.css');

$url = $this->assertGetURL();

$linkText = $this->getLinkText();
if ($linkText === false)
	$linkText = _t('Email to a friend');

$frameId = 'SERIA_RecommendByEMail_form_'.mt_rand();
echo seria_bml('a', array('href' => '#', 'onclick' => 'SERIA_RecommendByEMail.linkClick(\''.$frameId.'\', '.$this->getId().', \''.$url.'\'); return false;'))->setText($linkText)->output();
echo seria_bml('div', array('class' => 'SERIA_RecommendByEMail', 'id' => $frameId))->output();

?>