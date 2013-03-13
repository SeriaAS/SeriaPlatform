<?php

class SERIA_UserAuthenticationProvider extends SERIA_MetaObject
{
	public static function Meta($instance=NULL)
	{
		return array(
			'table' => '{user_authentication_provider}',
			'primaryKey' => 'id',
			'fields' => array(
				'id' => array('primarykey', _t('Primary key')),
				'authprovider' => array('name', _t('Authprovider class')),
				'id_unique' => array('name', _t('User unique identifier')),
				'user' => array('SERIA_User', _t('User object')),
				'email' => array('email', _t('E-mail')),
				'params' => array(
					'fieldtype' => 'text',
					'type' => 'blob',
					'validator' => new SERIA_Validator(array())
				)
			)
		);
	}

	public function get($name)
	{
		$value = parent::get($name);
		switch ($name) {
			case 'params':
				$value = unserialize($value);
				break;
		}
		return $value;
	}
	public function set($name, $value)
	{
		switch ($name) {
			case 'params':
				$value = serialize($value);
				break;
		}
		parent::set($name, $value);
	}

	/**
	 *
	 * A record is orphan if the user has been deleted.
	 * @return boolean
	 */
	public function isOrphan()
	{
		try {
			$user = $this->get('user');
		} catch (SERIA_NotFoundException $e) {
			return true;
		} catch (SERIA_Exception $e) {
			if ($e->getCode() == SERIA_Exception::NOT_FOUND)
				return true;
			else
				throw $e;
		}
		return false;
	}

	/**
	 *
	 * Query for provider by authprovider and unique user id.
	 * @param string $authproviderClass
	 * @param string $unique
	 * @return SERIA_UserAuthenticationProvider
	 */
	public static function getProvider($authproviderClass, $unique)
	{
		$query = new SERIA_MetaQuery('SERIA_UserAuthenticationProvider', 'authprovider = :authprovider AND id_unique = :id_unique', array('authprovider' => $authproviderClass, 'id_unique' => $unique));
		$obj = $query->current();
		$orphans = array();
		while ($obj && $obj->isOrphan()) {
			$orphans[] = $obj;
			$obj = $query->next();
		}
		foreach ($orphans as $orphan)
			SERIA_Meta::delete($orphan);
		if ($obj) {
			if ($query->next()) {
				if (defined('SERIA_DEBUG') && SERIA_DEBUG)
					throw new SERIA_Exception('Duplicate match for unique id ('.$authproviderClass.'::'.$unique.')');
			}
			return $obj;
		} else
			return null;
	}

	public static function deletingUser(SERIA_User $user)
	{
		$providers = SERIA_Meta::all('SERIA_UserAuthenticationProvider')->where('user = :user', array('user' => $user->get('id')));
		foreach ($providers as $provider) {
			SERIA_Meta::delete($provider);
		}
	}
}