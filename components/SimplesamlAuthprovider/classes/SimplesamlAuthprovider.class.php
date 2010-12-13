<?php

abstract class SimplesamlAuthprovider extends SERIA_GenericAuthprovider
{
	protected $simplesamlConfig = null;

	public function getConfig()
	{
		if ($this->simplesamlConfig !== null)
			return $this->simplesamlConfig;
		$config = SERIA_Base::getParam($this->getProviderId().'.config');
		if ($config)
			$config = unserialize($config);
		else
			$config = array();
		$modified = false;
		if (!isset($config['auth.adminpassword'])) {
			$modified = true;
			$config['auth.adminpassword'] = sha1(mt_rand().mt_rand().mt_rand().mt_rand().mt_rand().mt_rand());
		}
		if (!isset($config['technicalcontact_name'])) {
			$modified = true;
			$config['technicalcontact_name'] = 'John Doe';
		}
		if (!isset($config['technicalcontact_email'])) {
			$modified = true;
			$config['technicalcontact_email'] = 'unspecified@example.com';
		}
		if ($modified)
			SERIA_Base::setParam($this->getProviderId().'.config', serialize($config));
		return $this->simplesamlConfig = $config;
	}
	public function getConfigParameter($name)
	{
		$config = $this->getConfig();
		if (isset($config[$name]))
			return $config[$name];
		else
			return null;
	}
	public function setConfigParameter($name, $value)
	{
		if ($this->simplesamlConfig === null)
			$this->getConfig();
		$this->simplesamlConfig[$name] = $value;
		SERIA_Base::setParam($this->getProviderId().'.config', serialize($this->simplesamlConfig));
	}
	
	/**/
	//public abstract function getProviderId();
	//public abstract function getName();
	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		/*
		 * XXX - Using forceFetch because SERIA_Base::getParam caching is severely broken.
		 */
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				return (SERIA_Base::getParam('simplesaml_provider.'.$this->getProviderId().'.system_enabled', true) ? true : false);
			case SERIA_IAuthprovider::LOGIN_GUEST:
				return (SERIA_Base::getParam('simplesaml_provider.'.$this->getProviderId().'.guest_enabled', true) ? true : false);
			default:
				return false;
		}
	}
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				SERIA_Base::setParam('simplesaml_provider.'.$this->getProviderId().'.system_enabled', ($enabled ? 1 : 0));
				break;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				SERIA_Base::setParam('simplesaml_provider.'.$this->getProviderId().'.guest_enabled', ($enabled ? 1 : 0));
				break;
		}
	}
	public function configSimplesaml()
	{
		SERIA_Base::debug('Configuring SimpleSAML for launch...');
		if (!($secretSalt = SERIA_Base::getParam('simplesaml_secret_salt'))) {
			$secretSalt = sha1(mt_rand().mt_rand().mt_rand().mt_rand().SERIA_ROOT);
			SERIA_Base::setParam('simplesaml_secret_salt', $secretSalt);
		}
		$defaultAuthsources = array(
		);
		$defaultConfig = $this->getConfig();
		$defaultConfig['secretsalt'] = $secretSalt;
		$params = $this->getParameters();
		if (!isset($params['authsources']))
			$params['authsources'] = array();
		if (!isset($params['config']))
			$params['config'] = array();
		$params['authsources'] = array_merge($defaultAuthsources, $params['authsources']);
		$params['config'] = array_merge($defaultConfig, $params['config']);
		foreach ($params['authsources'] as $name => $vparams)
			SimplesamlSystem::registerAuthsource($name, $vparams);
		foreach ($params['config'] as $name => $vparams)
			SimplesamlSystem::registerConfig($name, $vparams);
		foreach ($params['metadata'] as $type => $data)
			SimplesamlSystem::registerMetadata($type, $data);
		if (!isset($params['authsource']))
			throw new SERIA_Exception('Set an authsource in the params array!');
		SimplesamlSystem::hooks();
		return $params;
	}
	protected function startSimplesaml()
	{
		SERIA_Base::debug('Starting SimpleSAML');
		$params = $this->configSimplesaml();
		SimplesamlSystem::start();
		SERIA_Base::debug('SimpleSAML is loaded and ready to receive calls..');
		return $params;
	}
	protected function callLoginManager($class, $providerId, $params, $attributes, $guestLogin)
	{
		$mgr = new SimplesamlLoginManager();
		$mgr->login($class, $providerId, $params, $attributes, $guestLogin);
	}
	public function authenticate($interactive=true, $reset=false, $guestLogin=false)
	{
		if (isset($_GET['returned'])) {
			/*
			 * Returned from the simplesaml authentication.
			 */
			$params = $this->startSimplesaml();
			$as = new SimpleSAML_Auth_Simple($params['authsource']);
			if ($as->isAuthenticated()) {
				return $this->authenticatedExternally($params, $as->getAttributes(), $guestLogin);
			}
			/* Login failed */
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
			die();
		}
		if (isset($_GET['failureReturn'])) {
			/*
			 * Returned with failure.
			 */
			$state = new SERIA_AuthenticationState();
			$providerId = $this->getProviderId();
			$url = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/components/SimplesamlAuthprovider/pages/loginFailed.php');
			$url->setParam('provider', $providerId);
			$url = $state->stampUrl($url);
			SERIA_Base::redirectTo($url->__toString());
			die();
		}
		$returnTo = SERIA_HTTP_ROOT;
		while (substr($returnTo, -1) == '/')
			$returnTo = substr($returnTo, 0, -1);
		$returnTo .= $_SERVER['REQUEST_URI'];
		$pos = strpos($returnTo, '?');
		if ($pos !== false) {
			if ($pos == (strlen($returnTo) - 1))
				$returnTo .= 'returned=yes';
			else
				$returnTo .= '&returned=yes';
		} else
			$returnTo .= '?returned=yes';
		$params = $this->startSimplesaml();
		$url = new SimplesamlRedirect($returnTo);
		$url = $url->__toString();
		$failureUrl = SERIA_Url::current();
		$failureUrl->setParam('failureReturn', 'y');
		$failureUrl = new SimplesamlRedirect($failureUrl->__toString());
		$failureUrl = $failureUrl->__toString();
		$as = new SimpleSAML_Auth_Simple($params['authsource']);
		$as->requireAuth(array(
			'ReturnTo' => $url,
			'ReturnFailureTo' => $failureUrl
		));
		return $this->authenticatedExternally($params, $as->getAttributes(), $guestLogin);
	}
	public function logout()
	{
		if (SERIA_DEBUG)
			SERIA_Base::debug('Logout: '.$this->getName());
		$params = $this->startSimplesaml();
		$as = new SimpleSAML_Auth_Simple($params['authsource']);
		if ($as->isAuthenticated()) {
			SERIA_Base::debug('Logging out with SimpleSAML');
			$as->logout();
			SERIA_Base::debug('SimpleSAML returned after logout.');
		}
	}
}