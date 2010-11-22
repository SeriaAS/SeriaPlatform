<?php

class SERIA_LocalAuthprovider extends Exception implements SERIA_IAuthprovider
{
	public static function loadProviders()
	{
		$local_auth = new SERIA_LocalAuthprovider();
		SERIA_Authproviders::addProvider($local_auth);
		return array(
			'specialConfig' => array(
				'call' => array('SERIA_LocalAuthprovider', 'getSpecialConfig')
			) 
		);
	}

	public static function getSpecialConfig(&$object)
	{
		return array(
			'configAutoNotSupported' => true
		);
	}
	/**/
	public function getProviderId()
	{
		return 'local';
	}
	public function getName()
	{
		return _t('Local authentication');
	}
	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				if (SERIA_Base::getParam('local_authprovider_disabled') == 1)
					return false;
				else
					return true;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				if (SERIA_Base::getParam('local_authprovider_guest_disabled') == 1)
					return false;
				else
					return true;
			default:
				return false;
		}
	}
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				SERIA_Base::setParam('local_authprovider_disabled', $enabled ? 0 : 1);
				break;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				SERIA_Base::setParam('local_authprovider_guest_disabled', $enabled ? 0 : 1);
				break;
		}
	}
	public function isAvailable()
	{
		return true; /* Always available */
	}
	public function authenticate($interactive=true, $reset=false, $guestLogin=false)
	{
		throw $this; /* This is a special case */
	}
	public static function automaticDiscoveryPreCheck()
	{
		return false;
	}
	public function automaticDiscovery()
	{
		return false;
	}
	public function logout()
	{
	}
}