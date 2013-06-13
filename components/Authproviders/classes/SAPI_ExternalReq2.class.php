<?php

class SAPI_ExternalReq2 extends SAPI
{
	protected static function getUserDataArray(SERIA_User $user)
	{
		$fields = array(
			"firstName",
			"lastName",
			"displayName",
			"username",
			"email",
			'is_administrator',
			'guestAccount'
		);
		$values = SERIA_ExternalReq2ExtensionValues::getObject($user)->getValues();
		$values['uid'] = $user->get('id');
		foreach ($fields as $field)
			$values[$field] = $user->get($field);
		$values['safeEmails'] = SERIA_SafeEmailUsers::getSafeEmailAddresses($user);
		return $values;
	}
	public static function getUserData($requestToken)
	{
		SERIA_ProxyServer::noCache();
		$access = new SERIA_ExternalReq2Token($requestToken);
		$data = $access->getData();
		try {
			if ($access->validateToken()) {
				$user = SERIA_User::createObject($data['uid']);
				return self::getUserDataArray($user);
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
				return self::getUserDataArray($user);
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
	public static function getBaseUrl()
	{
		return SERIA_HTTP_ROOT;
	}
}
