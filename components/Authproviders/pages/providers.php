<?php

require(dirname(__FILE__).'/../../../main.php');

SERIA_Base::pageRequires('admin');

$app = SERIA_Components::getComponent('seria_authproviders');
if (!$app)
	throw new SERIA_Exception('Authproviders component is not registered.');
SERIA_Authproviders::loadProviders();
$gui = new SERIA_Gui($app->getName());
$gui->title(_t('Seria authentication providers'));
$gui->activeMenuItem('controlpanel/settings/authproviders/providers');

$providers = SERIA_Authproviders::getProviders();
$creators = SERIA_Authproviders::getCreators();

if (sizeof($_POST)) {
	foreach ($providers as &$provider) {
		if (isset($_POST[$provider->getProviderId().'_present'])) {
			$provider->setEnabled(isset($_POST[$provider->getProviderId().'_system_enable']) && $_POST[$provider->getProviderId().'_system_enable']);
			$provider->setEnabled(isset($_POST[$provider->getProviderId().'_guest_enable']) && $_POST[$provider->getProviderId().'_guest_enable'], SERIA_IAuthprovider::LOGIN_GUEST);
			$provider->setEnabled(isset($_POST[$provider->getProviderId().'_auto_enable']) && $_POST[$provider->getProviderId().'_auto_enable'], SERIA_IAuthprovider::LOGIN_AUTO);
		}
	}
	unset($provider);
	$url = SERIA_HTTP_ROOT;
	$len = strlen($url);
	if ($len > 1  && substr($url, $len - 1) == '/')
		$url = substr($url, 0, $len - 1);
	$url .= $_SERVER['REQUEST_URI'];
	SERIA_Base::redirectTo($url);
	die();
}

$provider_info = array();
foreach ($providers as &$provider) {
	$info = array();
	$info['provider'] =& $provider;
	$ops = SERIA_Authproviders::getProviderOperations($provider);
	if (isset($ops['specialConfig'])) {
		$info['supports'] = array();
		$config = call_user_func($ops['specialConfig']['call'], $provider);
		$possiblyUnsupported = array(
			'system' => 'configSystemNotSupported',
			'guest' => 'configGuestNotSupported',
			'auto' => 'configAutoNotSupported'
		);
		foreach ($possiblyUnsupported as $name => $key) {
			if (!isset($config[$key]) || !$config[$key])
				$info['supports'][] = $name;
		}
	} else
		$info['supports'] = array('system', 'guest', 'auto');
	$ops = SERIA_Authproviders::getProviderOperations($provider);
	$info['configure'] = ($ops && isset($ops['configure']) ? (SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/configure.php?id='.urlencode($provider->getProviderId())) : false);
	$info['delete'] = ($ops && isset($ops['delete']) ? (SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/delete.php?id='.urlencode($provider->getProviderId())) : false);
	$provider_info[] =& $info;
	unset($info);
}
unset($provider);

$params = array(
	'app' => $app,
	'enabled' => SERIA_Base::getParam('authproviders_application_enabled'),
	'providers' => &$provider_info,
	'creators' => &$creators
);

$str = $app->parseTemplateToString('providers', $params);

$gui->contents($str);

echo $gui->output();

?>
