<?php
	class SERIA_CDNServer extends SERIA_FluentObject
	{
		static function createObject($p=false) 	{ return SERIA_Fluent::createObject('SERIA_CDNServer', $p); }
		static function fromDB($row) 		{ return SERIA_Fluent::createObject('SERIA_CDNServer', $row); }
		public static function fluentSpec() 	{ return array('table'=>'{cdn_servers}','primaryKey'=>'id');}
		public function isDeletable() {
			if(empty($this->row['id']))
				return false;
			
			if(SERIA_Base::isElevated() || SERIA_Base::isAdministrator() || SERIA_Base::userId()===$this->row['ownerId'])
				return true;

			return false;
		}

		/**
		* Defines field types and rules for the row that thit object represents. Used for validation
		*/
		public static function getFieldSpec()
		{
			return array(
				'name' => array(
					'fieldtype' => 'text',
					'caption' => _t('Name'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 2),
						array(SERIA_Validator::MAX_LENGTH, 30),
					)),
				),
				'ip' => array(
					'fieldtype' => 'text',
					'caption' => _t('IP-address'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::IP_ADDRESS),
						array(SERIA_Validator::FLUENT_UNIQUE, _t('IP-address already registered')),
					)),
				),
				'ownerId' => array(
					'fieldtype' => 'SERIA_User',
					'caption' => _t('Owner'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::FLUENT_OBJECT, 'SERIA_User'),
					)),
				),
				'createdBy' => 'createdBy',
				'createdDate' => 'createdDate',
				'modifiedBy' => 'modifiedBy',
				'modifiedDate' => 'modifiedDate',
			);
		}

		public function getHostnames() {
			return SERIA_Fluent::all('SERIA_CDNHostname')->where('serverId=:id', array('id' => $this->getKey()));
		}

		public function getOwner() {
			return SERIA_Fluent::createObject('SERIA_User', $this->row['ownerId']);
		}
	}
