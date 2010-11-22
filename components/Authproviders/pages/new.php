<?php

require(dirname(__FILE__).'/../../../main.php');

SERIA_Base::pageRequires('admin');

$app = SERIA_Components::getComponent('seria_authproviders');
if (!$app)
	throw new SERIA_Exception('Authproviders component is not registered.');
SERIA_Authproviders::loadProviders();
$gui = new SERIA_Gui($app->getName());
$gui->title(_t('Seria authentication providers'));
$gui->activeMenuItem('controlpanel/settings/authproviders/providers/new');

$creators = SERIA_Authproviders::getCreators();
if (!isset($_GET['id'])) {
	$gui->contents($app->parseTemplateToString('new', array(
		'app' => $app,
		'creators' => &$creators
	)));
	echo $gui->output();
	return;
}

foreach ($creators as &$creator) {
	if ($creator['id'] == $_GET['id']) {
		ob_start();
		try {
			call_user_func($creator['call']);
		} catch (Exception $e) {
			ob_end_clean();
			throw $e;
		}
		$gui->contents(ob_get_clean());
		echo $gui->output();
		return;
	}
}
unset($creator);
throw new SERIA_Exception('Unknown creator: '.$_GET['id']);
