<?php

class SERIA_AuthproviderFault extends SERIA_MetaObject
{
	const NOTICE = 1;
	const WARNING = 2;
	const ERROR = 3;

	public static function Meta($instance = null)
	{
		return array(
			'table' => '{authproviders_faults}',
			'fields' => array(
				'type' => array('integer', _t('Fault type')),
				'trace' => array('text', _t('Trace')),
				'message' => array('text', _t('Message')),
				'data' => array('text', _t('Data'))
			)
		);
	}

	public static function recordFaultMessage($type, $message, $data=array())
	{
		$faultObject = new self();
		$faultObject->set('type', $type);
		ob_start();
		debug_print_backtrace();
		$faultObject->set('trace', ob_get_clean());
		$faultObject->set('message', $message);
		$faultObject->set('data', serialize($data));
		SERIA_Meta::save($faultObject);
	}
}