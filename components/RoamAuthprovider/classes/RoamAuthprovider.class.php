<?php

class RoamAuthprovider implements SERIA_IAuthprovider
{
	public static function loadProviders()
	{
		SERIA_Authproviders::addProvider(new RoamAuthprovider());
		return array(
			'specialConfig' => array(
				'call' => array('RoamAuthprovider', 'getSpecialConfig')
			)
		);
	}

	public static function getSpecialConfig(&$object)
	{
		return array(
			'configSystemNotSupported' => true,
			'configGuestNotSupported' => true
		);
	}

	/**/
	public function getProviderId()
	{
		return 'roam_auth_provider';
	}
	public function getName()
	{
		return _t('RoamAuth authprovider');
	}
	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_AUTO:
				return SERIA_Base::getParam('roam_auth_provider_enabled') ? true : false;
			default:
				return false;
		}
	}
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_AUTO:
				SERIA_Base::setParam('roam_auth_provider_enabled', $enabled ? 1 : 0);
		}
	}
	public function isAvailable()
	{
		return true;
	}
	public function authenticate($interactive=true, $reset=false, $guestLogin=false)
	{
		return SERIA_Base::user() ? true : false;
	}
	public function findUserByRoamAuthData($simpleUserData)
	{
		/*
		 * Check hostname whitelist.
		 */
		SERIA_Base::debug('DANGER: There is no whitelist for checking RoamAuth trust!');
		/*
		 * Authenticate the user
		 */
		$users = SERIA_PropertyList::query('SERIA_User', 'externalUser:'.$simpleUserData['hostname'], $simpleUserData['uid']);
		if (count($users) > 1)
			throw new SERIA_Exception('One of our users of simplesaml:'.$authproviderId.' has managed to get duplicate accounts.');
		if (count($users) == 1) {
			$user = $users[0];
			if (isset($simpleUserData['meta']) && $simpleUserData['meta']) {
				foreach ($simpleUserData['meta'] as $name => $value) {
					SERIA_Base::debug('META update: '.$name.' => '.$value);
					$user->setMeta($name, $value);
				}
			}
			return $user; /* Good, that was all */
		}
		/*
		 * Register a new user..
		 */
		$user = new SERIA_User();
		$init = array(
			'is_administrator' => 0,
			'enabled' => 1,
			'password_change_required' => 0,
			'password' => 'local_blocked_random_'.sha1(mt_rand().mt_rand().mt_rand().mt_rand().mt_rand()),
			'guestAccount' => 1 /* No access-level control for roaming */
		);
		foreach ($init as $name => $value)
			$user->set($name, $value);
		$xferFields = array(
			'firstName',
			'lastName',
			'displayName',
			'email'
		);
		foreach ($xferFields as $xfer)
			$user->set($xfer, $simpleUserData[strtolower($xfer)]);
		$user->set('username', $simpleUserData['uid'].'@'.$simpleUserData['hostname']);
		try {
			SERIA_Base::elevateUser(array($user, 'validate'));
		} catch (SERIA_ValidationException $e) {
			$errors = $e->getValidationErrors();
			if (isset($errors['displayName'])) {
				/* Display name conflict: try to resolve */
				$genDisp = $simpleUserData['displayName'].' at '.$simpleUserData['hostname'];
				$user->set('displayName', $genDisp);
				try {
					SERIA_Base::elevateUser(array($user, 'validate'));
				} catch (SERIA_ValidationException $e) {
					$errors = $e->getValidationErrors();
					if (isset($errors['displayName'])) {
						/* Second conflict, try a few more times to resolve this. */
						$num = 0;
						while (true) {
							$user->set('displayName', $genDisp.' '.$num);
							$valid = false;
							try {
								SERIA_Base::elevateUser(array($user, 'validate'));
								$valid = true;
							} catch (SERIA_ValidationException $e) {
								$errors = $e->getValidationErrors();
								if (!isset($errors['displayName']))
									throw $e;
								if ($num >= 10)
									throw $e; /* No more tries */
							}
							if ($valid)
								break;
							$num++;
						}
					}
				}
			} else
				throw $e;
		}
		if (SERIA_Base::elevateUser(array($user, 'save'))) {
			/* Get a clean object, just to be sure. */
			$uid = $user->get('id');
			if (!$uid || !is_numeric($uid))
				throw new SERIA_Exception('Unexpected invalid user-id');
			$user = SERIA_User::createObject($uid);
			if (!$user)
				throw new SERIA_Exception('User-object nonex. or otherwise evaluating to false');
		} else
			throw new SERIA_Exception('Failed to save user');
		/* Finished. Let them go ahead.. */
		$plist = SERIA_PropertyList::createObject($user);
		$plist->set('externalUser:'.$simpleUserData['hostname'], $simpleUserData['uid']);
		$plist->save();
		return $user;
	}
	public function getRoamAuthUrlData($authUrl)
	{
		$cache = self::cache();
		if(!($xmlData = $cache->get('xmldata:'.$authUrl))) {
			$cache->set('xmldata:'.$authUrl, $xmlData = file_get_contents($authUrl));
		}
		$simpleUserData = SERIA_UserLoginXml::parseXml($xmlData);
		if ($simpleUserData) {
			$requiredFields = array(
				'hostname',
				'uid',
				'username',
				'firstname',
				'lastname',
				'displayname'
			);
			foreach ($requiredFields as $check) {
				if (!isset($simpleUserData[$check])) {
					print_r(array(
						'simple' => $simpleUserData,
						'xml' => $vals,
						'index' => $index
					));
					SERIA_Base::debug('Got RoamAuth XML without required fields.');
					return false;
				}
			}
			/*
			 * Check that the hostname is valid for this url
			 */
			if (parse_url($authUrl, PHP_URL_HOST) != $simpleUserData['hostname']) {
				SERIA_Base::debug('Got RoamAuth XML with faked or incorrect hostname.');
				return false;
			}

			return $simpleUserData;
		}
		return false;
	}
	public function findUserByRoamAuthUrl($authUrl)
	{
		$simpleUserData = self::getRoamAuthUrlData($authUrl);
		if ($simpleUserData)
			return self::findUserByRoamAuthData($simpleUserData);
		return null;
	}
	public static function automaticDiscoveryPreCheck()
	{
		if (($authUrl = RoamAuth::_getRoamAuthUrl()) !== false) {
			SERIA_Base::debug('Trying roaming authentication.');
			$cache = self::cache();
			if(!($xmlData = $cache->get('xmldata:'.$authUrl)))
			{
				$cache->set('xmldata:'.$authUrl, $xmlData = file_get_contents($authUrl));
			}
			$simpleUserData = self::getRoamAuthUrlData($authUrl);
			if ($simpleUserData) {
				/*
				 * Limit cacheability..
				 */
				SERIA_ProxyServer::privateCache(600); /* 10 minutes private cache */
				/*
				 * Create a session if we don't have one already.
				 */
				if (isset($_GET['PHPSESSID']) && $_GET['PHPSESSID'])
					throw new SERIA_Exception('Sending of PHPSESSID in combination with UserXML is not allowed!');
				if (!session_id()) {
					session_id(sha1(serialize($simpleUserData)));
					session_start();
				}
				$_SESSION['roamAuthData'] = array(
					$authUrl => $simpleUserData
				);
				SERIA_Base::debug('Passed RoamAuth initial automatic discovery check.');
				return true;
			}
		}
		return false;
	}
	public function automaticDiscovery()
	{
		if (($authUrl = RoamAuth::_getRoamAuthUrl()) !== false) {
			if (isset($_SESSION['roamAuthData']) && isset($_SESSION['roamAuthData'][$authUrl])) {
				SERIA_Base::debug('Trying to authenticate by RoamAuth.');
				$simpleUserData = $_SESSION['roamAuthData'][$authUrl];
				$user = self::findUserByRoamAuthData($simpleUserData);
				SERIA_Base::user($user);
				return true;
			}
		}
		return false;
	}
	public function logout()
	{
	}

	public static function cache() {
		static $cache = false;
		if($cache) return $cache;
		return $cache = new SERIA_Cache('RoamAuthprovider');
	}
}
