<?php

class SERIA_AuthprovidersConfiguration2
{
	/**
	 * 
	 * Local authentication or external
	 * @return mixed Returns authentication provider or FALSE.
	 */
	public static function usingExternalAuthentication()
	{
		SERIA_Authproviders::loadProviders();
		$providers = SERIA_Authproviders::getProviders();
		foreach ($providers as $provider) {
			$classname = get_class($provider);
			switch ($classname) {
				case 'SERIA_ExternalAuthprovider':
					if ($provider->isEnabled(SERIA_IAuthprovider::LOGIN_SYSTEM) && $provider->isEnabled(SERIA_IAuthprovider::LOGIN_GUEST)) {
						SERIA_Base::debug('Using external authentication: '.$provider->getName());
						return $provider;
					}
					break;
			}
		}
		SERIA_Base::debug('Not using external authentication.');
		return false;
	}
	public static function providerIsEnabled($provider)
	{
		$enabled = $provider->isEnabled(SERIA_IAuthprovider::LOGIN_GUEST) && $provider->isEnabled(SERIA_IAuthprovider::LOGIN_SYSTEM);
		SERIA_Base::debug('Provider '.$provider->getName().' ('.$provider->getProviderId().') is '.($enabled ? '' : 'not ').'enabled.');
		return $enabled;
	}
	public static function disableProvider($provider)
	{
		SERIA_Base::debug('Disabling provider: '.$provider->getName().' ('.$provider->getProviderId().')');
		$provider->setEnabled(false, SERIA_IAuthprovider::LOGIN_AUTO);
		$provider->setEnabled(false, SERIA_IAuthprovider::LOGIN_GUEST);
		$provider->setEnabled(false, SERIA_IAuthprovider::LOGIN_SYSTEM);
	}
	public static function enableProvider($provider)
	{
		SERIA_Base::debug('Enabling provider: '.$provider->getName().' ('.$provider->getProviderId().')');
		$provider->setEnabled(true, SERIA_IAuthprovider::LOGIN_AUTO);
		$provider->setEnabled(true, SERIA_IAuthprovider::LOGIN_GUEST);
		$provider->setEnabled(true, SERIA_IAuthprovider::LOGIN_SYSTEM);
	}
	public static function enableExternalProvider($hostname)
	{
		SERIA_Authproviders::loadProviders();
		$providers = SERIA_Authproviders::getProviders();
		foreach ($providers as $provider) {
			if (get_class($provider) == 'SERIA_ExternalAuthprovider') {
				if ($provider->get('remote') == $hostname) {
					self::enableProvider($provider);
					$provider->set('accessLevel', 2); /* Max admin */
					$provider->save();
					return;
				}
			}
		}
		SERIA_Base::db()->exec('INSERT INTO {external_authproviders} (remote, guest_enabled, system_enabled, auto_enabled, accessLevel) VALUES (:remote, 1, 1, 1, 2)', array('remote' => $hostname));
		SERIA_Authproviders::loadProviders(false, true); /* Reload all! */
		$providers = SERIA_Authproviders::getProviders();
		foreach ($providers as $provider) {
			if (get_class($provider) == 'SERIA_ExternalAuthprovider') {
				if ($provider->get('remote') == $hostname) {
					self::enableProvider($provider);
					$provider->save();
					return;
				}
			}
		}
		throw new SERIA_Exception('Failed to create a new external provider');
	}
	public static function getConfigurationForm()
	{
		$externalProvider = self::usingExternalAuthentication();
		$static_spec = array(
			'authsource' => array(
				'fieldtype' => 'select',
				'caption' => _t('Source of authentication'),
				'value' => ($externalProvider ? 'external' : 'local'),
				'validator' => new SERIA_Validator(array()),
				'values' => array(
					'local' => _t('Local authentication'),
					'external' => _t('External authentication')
				)
			),
			'external_hostname' => array(
				'fieldtype' => 'text',
				'caption' => _t('Hostname of external authentication server'),
				'value' => ($externalProvider ? $externalProvider->get('remote') : ''),
				'validator' => new SERIA_Validator(array())
			)
		);
		$dynamic_spec = array();
		$all_providers = SERIA_Authproviders::getProviders();
		$providers = array();
		$ext_providers = array();
		$roam_providers = array();
		$local_provider = null;
		foreach ($all_providers as $provider) {
			$classname = get_class($provider);
			if ($classname == 'SERIA_LocalAuthprovider') {
				if ($local_provider !== null)
					throw new SERIA_Exception('More than one local (user/pass platform) provider is not possible.');
				$local_provider = $provider;
				continue;
			}
			if ($classname != 'SERIA_ExternalAuthprovider' &&
			    $classname != 'RoamAuthprovider')
				$providers[] = $provider;
			else if ($classname == 'SERIA_ExternalAuthprovider')
				$ext_providers[] = $provider;
			else
				$roam_providers[] = $provider;
		}
		if ($local_provider === null)
			throw new SERIA_Exception('No local (user/pass platform) provider found');
		foreach ($providers as $provider) {
			$dynamic_spec[$provider->getProviderId()] = array(
				'fieldtype' => 'checkbox',
				'caption' => $provider->getName(),
				'value' => self::providerIsEnabled($provider),
				'validator' => new SERIA_Validator(array())
			);
		}
		$action = new SERIA_ActionForm('AuthprovidersConfiguration2');
		$spec = array_merge($static_spec, $dynamic_spec);
		foreach ($spec as $name => $fspec)
			$action->addField($name, $fspec, isset($fspec['value']) ? $fspec['value'] : null);
		if ($action->hasData()) {
			$errors = array();
			switch ($action->get('authsource')) {
				case 'local':
					SERIA_Base::debug('Selected local authentication.');
					foreach ($providers as $provider) {
						if ($action->get($provider->getProviderId()))
							self::enableProvider($provider);
						else
							self::disableProvider($provider);
					}
					foreach ($ext_providers as $provider)
						self::disableProvider($provider);
					foreach ($roam_providers as $provider)
						self::disableProvider($provider);
					self::disableProvider($local_provider);
					$local_provider->setEnabled(true, SERIA_IAuthprovider::LOGIN_SYSTEM);
					break;
				case 'external':
					SERIA_Base::debug('Selected external authentication.');
					if ($action->get('external_hostname')) {
						foreach ($providers as $provider)
							self::disableProvider($provider);
						foreach ($ext_providers as $provider)
							self::disableProvider($provider);
						foreach ($roam_providers as $provider)
							self::disableProvider($provider);
						self::disableProvider($local_provider);
						self::enableExternalProvider($action->get('external_hostname'));
					} else
						$errors['external_hostname'] = _t('Required.');
					break;
				default:
					SERIA_Base::debug('Neither local nor external selected. No changes made.');
					$errors['authsource'] = _t('Select either local or external authentication.');
			}
			if (!$errors)
				$action->success = true;
			else
				$action->errors = $errors;
		}
		return $action;
	}
	public static function getLocalProviderName($provider)
	{
		$classname = get_class($provider);
		switch ($classname) {
			case 'OpenidGoogleAuthprovider':
				$providerName = _t('Google');
				break;
			case 'TwitterAuthprovider':
				$providerName = _t('Twitter');
				break;
			case 'FacebookAuthprovider':
				$providerName = _t('Facebook');
				break;
			case 'FeideAuthprovider':
				$id = $provider->getProviderId();
				switch ($id) {
					case 'feide':
						$providerName = _t('Feide');
						break;
					case 'feide_test':
						$providerName = _t('Feide testing');
						break;
					case 'feide_openid':
						$providerName = _t('Feide public');
						break;
					default:
						$providerName = _t('Feide');
						break;
				}
				break;
			case 'WindowsLiveAuthprovider':
				$providerName = _t('Windows Live');
				break;
			case 'SERIA_LocalAuthprovider':
			case 'SERIA_ExternalAuthprovider':
			case 'RoamAuthprovider':
				$providerName = null;
				break;
			default:
				$providerName = $provider->getName();
		}
		return $providerName;
	}
	public static function needToBeConfigured($provider)
	{
		if ($provider->isAvailable())
			return false;
		$ops = SERIA_Authproviders::getProviderOperations($provider);
		if (isset($ops['configure']) && $ops['configure'])
			return true;
		return false;
	}
	public static function callProviderConfiguration($provider, $redirect, $cancelUrl, $submitCaption=null)
	{
		$ops = SERIA_Authproviders::getProviderOperations($provider);
		if (isset($ops['configure']) && $ops['configure']) {
			if (isset($ops['configure']['version']) && $ops['configure']['version'] >= 2)
				call_user_func($ops['configure']['call'], $provider, array(
					'redirect' => $redirect, /* Version 2 has this array with parameter redirect */
					'submitCaption' => $submitCaption,
					'cancel' => $cancelUrl
				));
			else
				throw new SERIA_Exception('This authprovider does not support call configure version 2: '.$provider->getName());
		}
	}
}