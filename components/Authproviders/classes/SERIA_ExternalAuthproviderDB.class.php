<?php

class SERIA_ExternalAuthproviderDB extends SERIA_MetaObject
{
		public static function Meta($instance = NULL)
		{
			return array(
				'table' => '{external_authproviders}',
				'primaryKey' => 'remote',
				'fields' => array(
					'remote' => array('hostname', _t('Remote host'), array('type' => 'varchar(250)')),
					'system_enabled' => array('boolean', _t('System enabled')),
					'guest_enabled' => array('boolean', _t('Guest enabled')),
					'auto_enabled' => array('boolean', _t('Auto enabled')),
					'accessLevel' => array('integer minval(0) maxval(2)', _t('Max access level')) /* 0-2 */
				)
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
