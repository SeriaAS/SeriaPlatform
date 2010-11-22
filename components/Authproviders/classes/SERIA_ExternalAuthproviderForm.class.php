<?php

class SERIA_ExternalAuthproviderForm extends SERIA_Form
{
	protected $rpc_client_id = null;
	protected $rpc_key = null;

	function __construct(SERIA_ExternalAuthprovider $object)
	{
		parent::__construct($object);
	}
	public static function getFormSpec()
	{
		return array(
			'remote' => array(),
			'rpc_client_id' => array(
				'fieldtype' => 'integer',
				'caption' => _t('RPC client ID'),
				'weight' => 0,
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED),
					array(SERIA_Validator::INTEGER)
				)),
				'default' => '',
				'helptext' => _t('You get this number from the administrator of the remote host. It is used for authenticating this server against the remote server.')
			),
			'rpc_key' => array(
				'fieldtype' => 'text',
				'caption' => _t('RPC key'),
				'weight' => 0,
				'validator' => new SERIA_Validator(array(
					array(SERIA_Validator::REQUIRED)
				)),
				'default' => '',
				'helptext' => _t('You get this key (kind of password) from the administrator of the remote host. It is used for authenticating this server against the remote server.')
			),
			'accessLevel' => array()
		);
	}
	public static function caption()
	{
		return _t('External Seria Platform authentication provider');
	}
	public function isDeletable()
	{
		return $this->object->isDeletable();
	}
	public function _handle($data)
	{
		$fluentspec = call_user_func(array(get_class($this->object), 'fluentSpec'));
		$insert = !$this->object->get($fluentspec['primaryKey']);
		$formSpec = self::getFormSpec();
		$fieldSpec = $this->getFieldSpec();
		foreach ($fieldSpec as $fieldName => $spec) {
			if(isset($data[$fieldName]) && ($insert || $fieldName != $fluentspec['primaryKey']))
				$this->object->set($fieldName, $data[$fieldName]);
		}
		foreach ($formSpec as $fieldName => $spec) {
			if (!isset($fieldSpec[$fieldName]) && isset($data[$fieldName]))
				$this->set($fieldName, $data[$fieldName]);
		}
		if ($insert) {
			$row = $this->object->toDB();
			$allow = array();
			foreach ($fieldSpec as $fieldName => $spec)
				$allow[$fieldName] = true;
			$allow[$fluentspec['primaryKey']] = true;
			$allow = array_keys($allow);
			SERIA_Base::db()->insert($fluentspec['table'], $allow, $row);
		} else
			$this->object->save();
		$this->save();
	}

	public function set($name, $value)
	{
		switch ($name) {
			case 'rpc_client_id':
				$this->rpc_client_id = $value;
				break;
			case 'rpc_key':
				$this->rpc_key = $value;
				break;
			default:
				parent::set($name, $value);
		}
	}
	public function get($name)
	{
		if ($this->rpc_client_id === null) {
			if ($this->object->get('remote')) {
				$q = SERIA_Base::db()->query('SELECT client_id, client_key FROM {rpc_remote_services} WHERE service = :service', array('service' => $this->object->get('remote')))->fetch(PDO::FETCH_NUM);
				if ($q) {
					$this->rpc_client_id = $q[0];
					$this->rpc_key = $q[1];
				} else {
					$this->rpc_client_id = '';
					$this->rpc_key = '';
				}
			} else {
				$this->rpc_client_id = '';
				$this->rpc_key = '';
			}
		}
		switch ($name) {
			case 'rpc_client_id':
				return $this->rpc_client_id;
			case 'rpc_key':
				return $this->rpc_key;
			default:
				return parent::get($name);
		}
	}
	public function save()
	{
		$row = SERIA_Base::db()->query('SELECT * FROM {rpc_remote_services} WHERE service = :remote', array('remote' => $this->object->get('remote')))->fetchAll();
		if ($row) {
			$row = $row[0];
			if ($row['service'] != $row['hostname'])
				throw new SERIA_Exception('RPC service with name '.$row['service'].' should point to a hostname with the same name. Please edit RPC settings to correct.');
			SERIA_Base::db()->update(
				'{rpc_remote_services}',
				array(
					'service' => $this->object->get('remote')
				),
				array(
					'client_id',
					'client_key'
				),
				array(
					'client_id' => $this->rpc_client_id,
					'client_key' => $this->rpc_key
				)
			);
		} else {
			SERIA_Base::db()->insert(
				'{rpc_remote_services}',
				array(
					'service',
					'hostname',
					'client_id',
					'client_key'
				),
				array(
					'service' => $this->object->get('remote'),
					'hostname' => $this->object->get('remote'),
					'client_id' => $this->rpc_client_id,
					'client_key' => $this->rpc_key
				)
			);
		}
	}
}
