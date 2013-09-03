<?php

class SAPI_UserAuthenticationProviders extends SAPI
{
	/**
	 *
	 * Get all login providers for this user.
	 * @return array Array of array('id' => id, 'class' => class, 'email' => email)
	 */
	public static function getProviders()
	{
		$user = SERIA_Base::user();
		if ($user === false)
			return array('error' => 'Not logged in!');
		$providers = new SERIA_UserAuthenticationProviders($user);
		$providers = $providers->getProviders();
		$p = array();
		foreach ($providers as $provider) {
			$provider = array(
				'id' => $provider->get('id'),
				'class' => $provider->get('authprovider'),
				'email' => $provider->get('email')
			);
			$p[] = $provider;
		}
		return $p;
	}
	public static function get_deleteProvider($id)
	{
		throw new SERIA_Exception('Not available on a GET-req');
	}
	public static function put_deleteProvider($id)
	{
		throw new SERIA_Exception('Not available on a PUT-req');
	}
	/**
	 *
	 * Delete the provider for this user.
	 * @param integer $id Provider id.
	 */
	public static function deleteProvider($id)
	{
		$user = SERIA_Base::user();
		if ($user === false)
			return array('error' => 'Not logged in!');
		try {
			$provider = SERIA_Meta::load('SERIA_UserAuthenticationProvider', $id);
		} catch (Exception $e) {
			return array('error' => $e->getMessage());
		}
		if ($provider->get('user')->get('id') !== $user->get('id'))
			return array('error' => 'Access denied!');

		/*
		 * Copied from the auth.ndla.no/index.php-code..
		 */
		$plist = SERIA_PropertyList::createObject($user);
		$refparams = $provider->get('params');
		if (isset($refparams['search2'])) {
			if ($plist->get($refparams['search2']['key']) == $refparams['search2']['value']) {
				if ($plist->get($refparams['search']['key']) == $refparams['search']['value']) {
					$plist->delete($refparams['search']['key']);
				}
				if ($refparams['safeEmail'] && ($email = $provider->get('email'))) {
					$providers = new SERIA_UserAuthenticationProviders($user);
					$providers = $providers->getProviders();
					foreach ($providers as $p) {
						if ($p->get('id') != $provider->get('id') && $p->get('email') == $email) {
							$email = false;
							break;
						}
					}
					if ($email)
						SERIA_SafeEmailUsers::deleteUserEmail($user, $email);
				}
				$plist->delete($refparams['search2']['key']);
				$plist->save();
				SERIA_Meta::delete($provider);
			} else
				return array('error' => 'WARNING: Delete failed: Mismatch! (ignored, '.$plist->get($refparams['search2']['key']).' != '.$refparams['search2']['value'].')');
		} else {
			/*
			 * Compat!
			 */
			if ($plist->get($refparams['search']['key']) == $refparams['search']['value']) {
				if ($refparams['safeEmail'] && ($email = $provider->get('email'))) {
					$providers = new SERIA_UserAuthenticationProviders($user);
					$providers = $providers->getProviders();
					foreach ($providers as $p) {
						if ($p->get('id') != $provider->get('id') && $p->get('email') == $email) {
							$email = false;
							break;
						}
					}
					if ($email)
						SERIA_SafeEmailUsers::deleteUserEmail($user, $email);
				}
				SERIA_Base::debug('Deleting: '.$refparams['search']['key']);
				$plist->delete($refparams['search']['key']);
				$plist->save();
			} else
				SERIA_Base::debug('WARNING: UNABLE TO DELETE IDENTIFIER MAP FOR USER (OVERWRITTEN BY ANOTHER ID?) Ignoring, and deleting reverse reference anyway...');
			SERIA_Meta::delete($provider);
		}
		return array('deleted' => $id);
	}

	/**
	 *
	 * Get urls to the auth-server to create new authprovider connections.
	 * @param string $returnUrl The url to return to regardless of success or failure.
	 */
	public static function getAddProviderUrls($returnUrl)
	{
		$loginType = SERIA_IAuthprovider::LOGIN_GUEST;
		$user = SERIA_Base::user();
		if ($user === false)
			return array('error' => 'Not logged in!');
		$exists = self::getProviders();
		foreach ($exists as &$ex) {
			$ex = $ex['class'];
		}
		SERIA_Authproviders::loadProviders();
		$adds = array();
		$linkUrls = SERIA_Authproviders::getAllIdLinkUrls($returnUrl);
		foreach ($linkUrls as $linkUrl) {
			if (array_search($linkUrl['class'], $exists) !== false)
				continue;
			$adds[] = array(
				'class' => $linkUrl['class'],
				'url' => $linkUrl['url']
			);
		}
		return $adds;
	}
}
