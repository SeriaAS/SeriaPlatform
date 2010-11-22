<?php
	class SERIA_Site extends SERIA_FluentObject
	{
		public static function getFieldSpec()
		{
			return array(
				'domain' => array(
					'fieldtype' => 'text',
					'caption' => _t('Title'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MAX_LENGTH, 100),
					)),
				),
				'title' => array(
					'fieldtype' => 'text',
					'caption' => _t('Title'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MAX_LENGTH, 100),
					)),
				),
				'created_date' => array(
					'caption' => _t('Created date'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
					)),
					'type' => 'datetime NOT NULL',
				),
				'created_by' => array(
					'fieldtype' => 'SERIA_User',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::FLUENT_OBJECT, 'SERIA_User'),
					)),
				),

			);
		}
		public static function fluentSpec() { 	return array('table' => '{sites}', 'primaryKey' => 'id'); }
		public static function fromDB($row) { return new SERIA_Site($row); }
		public function toDB() { return $this->row; }
		public function isDeletable() { return false; }







		public function getUsers()
		{
			return SERIA_Fluent::all('SERIA_User')->where('siteId=:id', $this);
		}

		public function getCreator()
		{
			return SERIA_Fluent::createObject('SERIA_User', $this->row['created_by']);
		}
	}
	
