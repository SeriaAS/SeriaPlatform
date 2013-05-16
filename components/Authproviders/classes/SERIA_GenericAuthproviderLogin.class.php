<?php

class SERIA_GenericAuthproviderLogin
{
	const LOGGED_IN_HOOK = 'SERIA_GenericAuthproviderLogin::LOGGED_IN_HOOK';

	public function getIdentityPropertyName()
	{
		return 'authprovidersUser:';
	}
	public function loggedIn($providerClass, $authproviderId, $params, $attributes)
	{
		SERIA_Hooks::dispatch(SERIA_GenericAuthproviderLogin::LOGGED_IN_HOOK, $providerClass, $authproviderId, $params, $attributes);
	}
	public function login($providerClass, $authproviderId, $params, $attributes, $guestLogin)
	{
		if (!$attributes['unique'])
			throw new SERIA_Exception('I need the unique attribute to be able to distinguish between users.');
		$users = SERIA_PropertyList::query('SERIA_User', sha1($this->getIdentityPropertyName().$authproviderId.'_'.$attributes['unique']), $attributes['unique']);
		if (!$users) {
			/*
			 * Compat!: Try the old identifier!
			 */
			$users = SERIA_PropertyList::query('SERIA_User', $this->getIdentityPropertyName().$authproviderId, $attributes['unique']);
			if ($users) {
				$user =& $users[0];
				$plist =& SERIA_PropertyList::createObject($user);
				$plist->set(sha1($this->getIdentityPropertyName().$authproviderId.'_'.$attributes['unique']), $attributes['unique']);
				if (!($list = $plist->get($this->getIdentityPropertyName().$authproviderId.'_list')))
					$list = array();
				if (!in_array($attributes['unique'], $list))
					$list[] = $attributes['unique'];
				$plist->set($this->getIdentityPropertyName().$authproviderId.'_list', $list);
				$plist->save();
				unset($plist);
				SERIA_Base::debug('Resolved user by the old identifier (Salvaged)');
				unset($user); /* Has been assigned by reference, next set would write into array */
			}
		}
		if (count($users) > 1)
			SERIA_AuthproviderFault::recordFaultMessage(SERIA_AuthproviderFault::WARNING, 'One of our users of authprovider:'.$authproviderId.' has managed to get duplicate accounts.', array('attr' => $attributes, 'matches' => $users));
		$user = SERIA_Base::user();
		if (count($users) >= 1)
			$user =& $users[0];
		else if ($user) {
			$plist = SERIA_PropertyList::createObject($user);
			/*
			 * Compat!: Don't use for identifying!
			 */
			$plist->set($this->getIdentityPropertyName().$authproviderId, $attributes['unique']);

			$plist->set(sha1($this->getIdentityPropertyName().$authproviderId.'_'.$attributes['unique']), $attributes['unique']);
			if (!($list = $plist->get($this->getIdentityPropertyName().$authproviderId.'_list')))
				$list = array();
			if (!in_array($attributes['unique'], $list))
				$list[] = $attributes['unique'];
			$plist->set($this->getIdentityPropertyName().$authproviderId.'_list', $list);
			$plist->save();
		}
		if ($user) {
			if (isset($params['safeEmail']) && $params['safeEmail'] && $attributes['email'])
				SERIA_SafeEmailUsers::registerUserEmail($user, $attributes['email']);
			$email = '';
			if ($attributes['email'])
				$email = $attributes['email'];
			$params = array(
				'search' => array(
					'key' => $this->getIdentityPropertyName().$authproviderId,
					'value' => $attributes['unique']
				),
				'search2' => array(
					'key' => sha1($this->getIdentityPropertyName().$authproviderId.'_'.$attributes['unique']),
					'value' => $attributes['unique']
				),
				'safeEmail'=> isset($params['safeEmail']) ? $params['safeEmail'] : false,
				'params' => $params,
				'attributes' => $attributes
			);
			$ref = new SERIA_UserAuthenticationProviders($user);
			$ref->setProvider($providerClass, $attributes['unique'], $email, $params);
			SERIA_Base::user($user);
			$this->loggedIn($providerClass, $authproviderId, $params, $attributes);
			return;
		}
		if (isset($params['safeEmail']) && $params['safeEmail'] && $attributes['email']) {
			/*
			 * Check if this email address has been registered.
			 */
			$user = SERIA_SafeEmailUsers::getUserByEmail($attributes['email']);
			if ($user) {
				$plist = SERIA_PropertyList::createObject($user);
				$plist->set($this->getIdentityPropertyName().$authproviderId, $attributes['unique']);
				$plist->set(sha1($this->getIdentityPropertyName().$authproviderId.'_'.$attributes['unique']), $attributes['unique']);
				if (!($list = $plist->get($this->getIdentityPropertyName().$authproviderId.'_list')))
					$list = array();
				if (!in_array($attributes['unique'], $list))
					$list[] = $attributes['unique'];
				$plist->set($this->getIdentityPropertyName().$authproviderId.'_list', $list);
				$plist->save();
				$params = array(
					'search' => array(
						'key' => $this->getIdentityPropertyName().$authproviderId,
						'value' => $attributes['unique']
					),
					'search2' => array(
						'key' => sha1($this->getIdentityPropertyName().$authproviderId.'_'.$attributes['unique']),
						'value' => $attributes['unique']
					),
					'safeEmail'=> true,
					'params' => $params,
					'attributes' => $attributes
				);
				$ref = new SERIA_UserAuthenticationProviders($user);
				$ref->setProvider($providerClass, $attributes['unique'], $attributes['email'], $params);
				SERIA_Base::user($user);
				$this->loggedIn($providerClass, $authproviderId, $params, $attributes);
				return;
			}
		}
		$state = new SERIA_AuthenticationState();
		/*
		 * Store the attributes.
		 */
		$linkid = 'link:'.mt_rand();
		$state->set($linkid, array(
			'providerClass' => $providerClass,
			'provider' => $authproviderId,
			'params' => $params,
			'attributes' => $attributes,
			'identityPropertyName' => $this->getIdentityPropertyName()
		));
		/*
		 * Redirect to register page
		 */
		$state = new SERIA_AuthenticationState();
		SERIA_Base::redirectTo($state->stampUrl(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/linkaccount.php?id='.urlencode($linkid).($guestLogin ? '&guest=yes' : '')));
		die();
	}
}