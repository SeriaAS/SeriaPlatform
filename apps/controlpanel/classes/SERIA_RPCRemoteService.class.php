<?php

class SERIA_RPCRemoteService extends SERIA_FluentObject
{
	static function createObject($p=false)
	{
		return SERIA_Fluent::createObject('SERIA_RPCRemoteService', $p);
	}
	static function fromDB($row)
	{
		return SERIA_Fluent::createObject('SERIA_RPCRemoteService', $row);
	}
	public static function fluentSpec()
	{
		return array('table' => '{rpc_remote_services}', 'primaryKey' => 'service');
	}
	public function isDeletable() {
		if(empty($this->row['id']))
			return false;
			
		if(SERIA_Base::isAdministrator())
			return true;

		return false;
	}

	/**
	* Defines field types and rules for the row that thit object represents. Used for validation
	*/
	public static function getFieldSpec()
	{
		return array(
			'service' => array(
				'fieldtype' => 'text',
				'caption' => _t('Service name'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED),
					array(SERIA_Validator::MAX_LENGTH, 120)
				))
			),
			'hostname' => array(
				'fieldtype' => 'text',
				'caption' => _t('Hostname'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED),
					array(SERIA_Validator::MAX_LENGTH, 60)
				))
			),
			'client_id' => array(
				'fieldtype' => 'text',
				'caption' => _t('Client ID'),
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED),
					array(SERIA_Validator::INTEGER)
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