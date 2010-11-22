<?php

class SERIA_RPCClientKey extends SERIA_FluentObject
{
	static function createObject($p=false)
	{
		return SERIA_Fluent::createObject('SERIA_RPCClientKey', $p);
	}
	static function fromDB($row)
	{
		return SERIA_Fluent::createObject('SERIA_RPCClientKey', $row);
	}
	public static function fluentSpec()
	{
		return array('table' => '{rpc_clients}', 'primaryKey' => 'client_id');
	}
	public function isDeletable() {
		if(empty($this->row['client_id']))
			return false;
			
		if(SERIA_Base::isAdministrator())
			return true;

		return false;
	}
	public static function getFieldSpec()
	{
		return array(
			'client_id' => array(
				'fieldtype' => 'text',
				'caption' => _t('Client ID'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::INTEGER)
				))
			),
			'name' => array(
				'fieldtype' => 'text',
				'caption' => _t('Client name'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED),
					array(SERIA_Validator::MAX_LENGTH, 120)
				))
			),
			'client_key' => array(
				'fieldtype' => 'text',
				'caption' => _t('Key'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED),
					array(SERIA_Validator::MAX_LENGTH, 60),
					array(SERIA_Validator::MIN_LENGTH, 10)
				))
			)
		);
	}
}