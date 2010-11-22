<?php

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_Base::pageRequires('admin');

if (!isset($_GET['id'])) {
	SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
	die();
}

SERIA_Authproviders::loadProviders();

$app = SERIA_Components::getComponent('seria_authproviders');
if (!$app)
	throw new SERIA_Exception('Authproviders component is not registered.');

$providers =& SERIA_Authproviders::getProviders();
foreach ($providers as &$provider) {
	if ($_GET['id'] == $provider->getProviderId()) {
		$ops = SERIA_Authproviders::getProviderOperations($provider);
		if (isset($ops['configure'])) {
			$gui = new SERIA_Gui($app->getName());
			$gui->title(_t('Seria authentication providers'));
			$gui->addMenuItem('controlpanel/settings/authproviders/providers/configure', _t('Configure provider: %NAME%', array('NAME' => $provider->getName())), _t('Configure this provider here.'), SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/configure.php?id='.urlencode($provider->getProviderId()), SERIA_HTTP_ROOT.'/seria/components/Authproviders/icon.png', 100);
			$gui->activeMenuItem('controlpanel/settings/authproviders/providers/configure');
			ob_start();
			try {
				call_user_func($ops['configure']['call'], $provider);
			} catch (Exception $e) {
				ob_end_clean();
				throw $e;
			}
			$gui->contents(ob_get_clean());
			echo $gui->output();
			return;
		}
	}
}
unset($provider);
unset($providers);

SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
die();
