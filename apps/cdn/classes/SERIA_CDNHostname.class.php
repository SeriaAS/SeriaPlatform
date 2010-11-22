<?php
	class SERIA_CDNHostname extends SERIA_FluentObject
	{
		public static function fluentSpec() 		{ return array('table'=>'{cdn_hostnames}','primaryKey'=>'id');}
		public static function createObject($p=false)	{ return SERIA_Fluent::createObject('SERIA_CDNHostname', $p);}
                public static function fromDB($row)		{ return SERIA_Fluent::createObject('SERIA_CDNHostname', $row);}
                public function isDeletable() {
                        if(empty($this->row['id']))
                                return false;

                        if(SERIA_Base::isElevated() || SERIA_Base::isAdministrator() || SERIA_Base::userId()===$this->getServer()->get('ownerId'))
                                return true;

                        return false;
                }

		public function getServer()
		{
			return SERIA_Fluent::createObject('SERIA_CDNServer', $this->row['serverId']);
		}

		public static function createByHostname($hostname)
		{
			$row = SERIA_Base::db()->query('SELECT * FROM {cdn_hostnames} WHERE hostname=:hostname', array(':hostname' => $hostname))->fetch(PDO::FETCH_ASSOC);
			if($row)
			{
				return SERIA_Fluent::createObject('SERIA_CDNHostname', $row);
			}
			else
			{
				// Lookup the IP, and optionally create the hostname
				$ip = gethostbyname($hostname);
				if($server = SERIA_Fluent::all('SERIA_CDNServer')->where('ip=:ip', array('ip' => $ip))->current())
				{
					if($server->getOwner()->hasRight('cdn_edit_servers_autohosts'))
					{
						$host = SERIA_Fluent::createObject('SERIA_CDNHostname');
						$host->set('createdBy', $server->getOwner()->getKey());
						$host->set('createdDate', time());
						$host->set('modifiedBy', $server->getOwner()->getKey());
						$host->set('modifiedDate', time());
						$host->set('hostname', $hostname);
						$host->set('serverId', $server->getKey());
						$host->save();
						return $host;
					}
				}

				throw new SERIA_Exception('No such hostname');
			}
		}

		/**
		* Defines field types and rules for the row that thit object represents. Used for validation
		*/
		public static function getFieldSpec()
		{
			return array(
				'serverId' => array(
					'fieldtype' => 'SERIA_CDNServer',
					'type' => 'int',
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::FLUENT_OBJECT, 'SERIA_CDNServer'),
					)),
				),
				'hostname' => array(
					'fieldtype' => 'text',
					'caption' => _t('Hostname'),
					'validator' => new SERIA_Validator(array(
						array(SERIA_Validator::REQUIRED),
						array(SERIA_Validator::MIN_LENGTH, 1),
						array(SERIA_Validator::MAX_LENGTH, 100),
						array(SERIA_Validator::FLUENT_UNIQUE, 'Hostname already registered'),
					)),
				),
                                'createdBy' => array(
                                        'fieldtype' => 'SERIA_User',
                                        'validator' => new SERIA_Validator(array(
                                                array(SERIA_Validator::REQUIRED),
                                                array(SERIA_Validator::FLUENT_OBJECT, 'SERIA_User'),
                                        )),
                                ),
                                'createdDate' => array(
					'caption' => _t('Created date'),
                                        'validator' => new SERIA_Validator(array(
                                                array(SERIA_Validator::REQUIRED),
                                        )),
                                        'type' => 'datetime NOT NULL',
                                ),
                                'modifiedBy' => array(
                                        'fieldtype' => 'SERIA_User',
                                        'validator' => new SERIA_Validator(array(
                                                array(SERIA_Validator::REQUIRED),
                                                array(SERIA_Validator::FLUENT_OBJECT, 'SERIA_User'),
                                        )),
                                ),
                                'modifiedDate' => array(
                                        'validator' => new SERIA_Validator(array(
                                                array(SERIA_Validator::REQUIRED),
                                        )),
                                        'type' => 'datetime NOT NULL',
                                ),
			);
		}
	}
