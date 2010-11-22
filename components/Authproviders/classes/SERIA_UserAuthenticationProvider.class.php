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
}