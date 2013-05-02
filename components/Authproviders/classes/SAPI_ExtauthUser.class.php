<?php

class SAPI_ExtauthUser extends SAPI
{
	/**
	 *
	 * Get a (guest) login-url returning back to continue.
	 *
	 * @param string $continue Continue-url.
	 */
	public static function loginUrl($continue)
	{
		return SERIA_User::loginUrl($continue)->__toString();
	}
	public static function logoutUrl($continue)
	{
		return SERIA_User::logoutAction($continue)->__toString();
	}
	public static function getUserData()
	{
		SERIA_ProxyServer::noCache();
		$user = SERIA_Base::user();
		if ($user !== false) {
			$fields = array(
				"firstName",
				"lastName",
				"displayName",
				"username",
				"email",
				'is_administrator',
				'guestAccount'
			);
			$values = array('loggedIn' => true, 'uid' => $user->get('id'));
			foreach ($fields as $field)
				$values[$field] = $user->get($field);
			$extvalues = SERIA_ExternalReq2ExtensionValues::getObject($user);
			$values['extensionValues'] = $extvalues->getValues();
			return $values;
		} else
			return array('loggedIn' => false);
	}
}
