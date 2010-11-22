<?php

class SERIA_RPCClientKeyForm extends SERIA_Form
{
	public static function getFormSpec()
	{
		return array(
			'client_id' => array(
				'caption' => _t('Client id:'),
				'fieldtype' => 'readonly'
			),
			'name' => array(
				'caption' => _t('Name:')
			),
			'client_key' => array(
				'caption' => _t('Client key:')
			)
		);
	}
	public static function caption()
	{
		return _t('Edit rpc client key');
	}
	public function isDeletable()
	{
		return $this->object->isDeletable();
	}
	public function _handle($data)
	{
		foreach($this->getFieldSpec() as $fieldName => $spec) {
			if(isset($data[$fieldName]) && $fieldName != 'client_id')
				$this->object->set($fieldName, $data[$fieldName]);
		}
		$this->object->save();
	}

	public function readonly($name, $attributes = array())
	{
		$attributes['disabled'] = 'disabled';
		return $this->text($name, $attributes);
	}
}
