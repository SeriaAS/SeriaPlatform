<?php

class SERIA_ExternalAuthproviderDB extends SERIA_FluentObject
{
		public static function getFieldSpec()
		{
			$pi = new SERIA_Url(SERIA_HTTP_ROOT);
			$host = $pi->getHost();
			$hostParts = explode(".", $host);
			while(sizeof($hostParts)>2)
				array_shift($hostParts);
			$host = ".".implode(".", $hostParts);

			$exampleHost = 'auth'.$host;

			return array(
				'remote' => array(
					'fieldtype' => 'text',
					'caption' => _t('Remote host'),
					'weight' => 0,
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MAX_LENGTH, 256)
					)),
					'default' => '',
					'helptext' => _t('Hostname of the remote host used for authentication, for example <em>%EXAMPLE%</em>.', array('EXAMPLE' => $exampleHost)),
				),
				'system_enabled' => array(
					'fieldtype' => 'integer',
					'caption' => _t('Enabled'),
					'weight' => 0,
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::INTEGER)
					)),
					'default' => '1',
					'helptext' => _t('Whether this external host right now is allowed to authenticate users for system login.')
				),
				'guest_enabled' => array(
					'fieldtype' => 'integer',
					'caption' => _t('Enabled'),
					'weight' => 0,
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::INTEGER)
					)),
					'default' => '1',
					'helptext' => _t('Whether this external host right now is allowed to authenticate users for guest login.')
				),
				'auto_enabled' => array(
					'fieldtype' => 'integer',
					'caption' => _t('Enabled'),
					'weight' => 0,
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::INTEGER)
					)),
					'default' => '1',
					'helptext' => _t('Whether this external host right now is allowed to automatically authenticate users.')
				),
				'accessLevel' => array(
					'fieldtype' => 'integer',
					'caption' => _t('Max access level'),
					'weight' => 0,
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::INTEGER),
						array(SERIA_Validator::MIN_VALUE, 0),
						array(SERIA_Validator::MAX_VALUE, 2)
					)),
					'default' => 0,
					'helptext' => _t('How much access this provider is allowed to give new users (guest/system/admin)')
				)
		);
		}
		public static function fluentSpec()
		{
			return array(
				'table' => '{external_authproviders}',
				'primaryKey' => 'remote'
			);
		}
		public static function fromDB($row)
		{
			return new self($row['remote']);
		}
		public static function createObject($p=false)
		{
			return new self($p);
		}
		public function isDeletable()
		{
			return true;
		}
}
