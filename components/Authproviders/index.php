<?php

require(dirname(__FILE__).'/../../main.php');

SERIA_Base::pageRequires('admin');


$app = SERIA_Components::getComponent('seria_authproviders');
if (!$app)
	throw new SERIA_Exception('Authproviders component is not registered.');
$gui = new SERIA_Gui($app->getName());
$gui->title(_t('Seria authentication providers'));
$gui->activeMenuItem('controlpanel/settings/authproviders');

if (sizeof($_POST)) {
	SERIA_Base::setParam('authproviders_publish_domain', (isset($_POST['publish_enabled']) && $_POST['publish_enabled'] ? $_POST['publish_domain'] : ''));
	$url = SERIA_HTTP_ROOT;
	$len = strlen($url);
	if ($len > 1  && substr($url, $len - 1) == '/')
		$url = substr($url, 0, $len - 1);
	$url .= $_SERVER['REQUEST_URI'];
	SERIA_Base::redirectTo($url);
	die();
}

$settings = array(
	'app' => $app,
	'enabled' => defined('SERIA_AUTHPROVIDERS_ENABLED') && SERIA_AUTHPROVIDERS_ENABLED,
	'published' => SERIA_Base::getParam('authproviders_publish_domain')
);

$str = $app->parseTemplateToString('admin', $settings);

$gui->contents($str);

echo $gui->output();

?>
