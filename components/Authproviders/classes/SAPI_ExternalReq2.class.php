<?php

class SAPI_ExternalReq2 extends SAPI
{
	public static function getUserData($requestToken)
	{
		SERIA_ProxyServer::noCache();
		$access = new SERIA_ExternalReq2Token($requestToken);
		$data = $access->getData();
		try {
			if ($access->validateToken()) {
				$user = SERIA_User::createObject($data['uid']);
				$fields = array(
					"firstName",
					"lastName",
					"displayName",
					"username",
					"email",
					'is_administrator',
					'guestAccount'
				);
				$values = array('uid' => $user->get('id'));
				foreach ($fields as $field)
					$values[$field] = $user->get($field);
				return $values;
			} else {
				return array('error' => $access->getError());
			}
		} catch (Exception $e) {
			if (SERIA_DEBUG)
				throw $e;
			return array('error' => 'User data request failed');
		}
	}
	public static function getUserSession($requestToken)
	{
		SERIA_ProxyServer::noCache();
		$access = new SERIA_ExternalReq2Token($requestToken);
		$data = $access->getData();
		try {
			if ($access->validateToken()) {
				$user = SERIA_User::createObject($data['uid']);
				SERIA_Base::user($user); /* <-- Log the user in! */
				$fields = array(
					"firstName",
					"lastName",
					"displayName",
					"username",
					"email",
					'is_administrator',
					'guestAccount'
				);
				$values = array('uid' => $user->get('id'));
				foreach ($fields as $field)
					$values[$field] = $user->get($field);
				$values['safeEmails'] = SERIA_SafeEmailUsers::getSafeEmailAddresses($user);
				return $values;
			} else {
				return array('error' => $access->getError());
			}
		} catch (Exception $e) {
			if (SERIA_DEBUG)
				throw $e;
			return array('error' => 'User session request failed');
		}
	}
	public static function checkLogin()
	{
		SERIA_ProxyServer::noCache();
		$user = SERIA_Base::user();
		if ($user !== false) {
			return array(
				'loggedIn' => true,
				'uid' => $user->get('id')
			);
		} else
			return array('loggedIn' => false);
	}
}
