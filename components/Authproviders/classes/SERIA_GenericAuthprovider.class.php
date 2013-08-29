<?php

abstract class SERIA_GenericAuthprovider implements SERIA_IAuthprovider
{
	public static function loadProviders()
	{
		return array(
			'specialConfig' => array(
				'call' => array('SimplesamlAuthprovider', 'getSpecialConfig')
			)
		);
	}

	public function getSpecialConfig(&$object)
	{
		return array(
			'configAutoNotSupported' => true
		);
	}

	public abstract function getParameters();

	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				return (SERIA_Base::getParam('generic_provider.'.$this->getProviderId().'.system_enabled') ? true : false);
			case SERIA_IAuthprovider::LOGIN_GUEST:
				return (SERIA_Base::getParam('generic_provider.'.$this->getProviderId().'.guest_enabled') ? true : false);
			default:
				return false;
		}
	}
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				SERIA_Base::setParam('generic_provider.'.$this->getProviderId().'.system_enabled', ($enabled ? 1 : 0));
				break;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				SERIA_Base::setParam('generic_provider.'.$this->getProviderId().'.guest_enabled', ($enabled ? 1 : 0));
				break;
		}
	}
	public function isAvailable()
	{
		return true;
	}
	public function filterAttributes($attributes)
	{
		return $attributes;
	}
	protected function callLoginManager($class, $providerId, $params, $attributes, $guestLogin, $interactive)
	{
		$mgr = new SERIA_GenericAuthproviderLogin();
		$mgr->login($class, $providerId, $params, $attributes, $guestLogin);
	}
	protected function authenticatedExternally($params, $attributes, $guestLogin, $interactive)
	{
		$recvAttr = $attributes;
		$mappings = $params['attributes'];
		$namespace = array();
		foreach ($mappings['defaults'] as $name => $value)
			$namespace[$name] = $value;
		foreach ($mappings['load'] as $store => $load) {
			if (is_array($load) && !is_string($load)) {
				if (isset($attributes[$load[0]]) && is_array($attributes[$load[0]]) && isset($attributes[$load[0]][$load[1]]))
					$namespace[$store] = $attributes[$load[0]][$load[1]];
			} else if (isset($attributes[$load])) {
				$namespace[$store] = $attributes[$load];
			}
		}
		foreach ($mappings['combinations'] as $store => $load) {
			$value = '';
			foreach ($load as $part)
				$value .= $namespace[$part];
			$namespace[$store] = $value;
		}
		$namespace = $this->filterAttributes($namespace);
		$attributes = array();
		foreach ($mappings['fillIn'] as $name)
			$attributes[$name] = $namespace[$name];
		if (!isset($attributes['unique'])) {
			SERIA_Base::debug('No unique attribute specified. Dumping received attributes..');
			foreach ($recvAttr as $nam => $val) {
				SERIA_Base::debug($nam.' => '.serialize($val));
			}
		}
		$this->callLoginManager(get_class($this), $this->getProviderId(), $params, $attributes, $guestLogin, $interactive);
		return true; /* Success */
	}

	public static function automaticDiscoveryPreCheck()
	{
		return false;
	} 
	public function automaticDiscovery()
	{
		return false;
	}
}