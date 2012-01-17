<?php

class SAPI_SAPI extends SAPI
{
	protected static function getCurrentUser()
	{
		$user = SERIA_Base::user();
		if (!$user)
			throw new SERIA_Exception('Login is required!');
		return $user;
	}
	/**
	 *
	 * Get an array (id => description(string)) of app-keys.
	 * @return array 
	 */
	public static function getAppKeys()
	{
		$user = self::getCurrentUser();
		$appKeys = SERIA_Meta::all('SAPI_Token')->where('user = :user', array('user' => $user->get('id')));
		$rv = array();
		foreach ($appKeys as $appKey) {
			$rv[$appKey->get('id')] = $appKey->get('description');
		}
		return $rv;
	}
	/**
	 *
	 * Get the app-key secret and description.
	 * @param string $id Id of the app-key.
	 * @return string Secret.
	 */
	public static function getAppKeyData($id)
	{
		$user = self::getCurrentUser();
		$appKey = SERIA_Meta::load('SAPI_Token', $id);
		if (SERIA_Base::isAdministrator() || $appKey->get('user')->get('id') == $user->get('id'))
			return array(
				'user' => $appKey->get('user')->get('id'),
				'description' => $appKey->get('description'),
				'secret' => $appKey->get('secret')
			);
		else
			throw new SERIA_Exception('You don\'t have access to this app-key!');
	}
	/**
	 *
	 * Create a new app key.
	 * @param string $description
	 * @return array Object data (id, secret, description).
	 */
	public static function createAppKey($description)
	{
		$user = self::getCurrentUser();
		$token = new SAPI_Token();
		$secret = md5(mt_rand().mt_rand().mt_rand().mt_rand());
		$token->set('secret', $secret);
		$token->set('description', $description);
		SERIA_Meta::save($token);
		return array(
			'id' => $token->get('id'),
			'secret' => $secret,
			'description' => $description
		);
	}
	public static function get_createAppKey()
	{
		throw new SERIA_Exception('This method is not available as GET!');
	}
	public static function delete_createAppKey()
	{
		throw new SERIA_Exception('This method is not available as DELETE!');
	}
	public static function deleteKey($id)
	{
		$appKey = SERIA_Meta::load('SAPI_Token', $id);
		return (SERIA_Meta::delete($appKey) ? true : false);
	}
	public static function get_deleteKey()
	{
		throw new SERIA_Exception('This method is not available as GET!');
	}
	public static function put_deleteKey()
	{
		throw new SERIA_Exception('This method is not available as PUT!');
	}
}