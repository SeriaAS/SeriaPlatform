<?php

class SERIA_OutboardApplication extends SERIA_Application
{
	function getId()
	{
		return 'seria_outboard_application';
	}
	function getName()
	{
		return _t('Seria Outboard Application');
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}
	function getHttpPath()
	{
		return SERIA_HTTP_ROOT.'/seria/apps/outboard/';
	}

	public static function showAlerter()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter.php');
		die();
	}
	public static function showMessage()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		$template->addVariable('id', false);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/messages.php');
		die();
	}
	public static function showChannel()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		$template->addVariable('id', false);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/channels.php');
		die();
	}
	public static function showEditMessage($params)
	{
		SERIA_Base::pageRequires('admin');
		$id = array_shift($params);
		$template = new SERIA_MetaTemplate();
		$template->addVariable('id', $id);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/messages.php');
		die();
	}
	public static function showEditChannel($params)
	{
		SERIA_Base::pageRequires('admin');
		$id = array_shift($params);
		$template = new SERIA_MetaTemplate();
		$template->addVariable('id', $id);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/channels.php');
		die();
	}
	public static function showSchedule($params)
	{
		SERIA_Base::pageRequires('admin');
		$id = array_shift($params);
		$template = new SERIA_MetaTemplate();
		$template->addVariable('id', $id);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/schedule.php');
		die();
	}
	public static function showAddScheduledMessage($params)
	{
		SERIA_Base::pageRequires('admin');
		$message = array_shift($params);
		$template = new SERIA_MetaTemplate();
		$template->addVariable('messageId', $message);
		$template->addVariable('id', false);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/schedulemessage.php');
		die();
	}
	public static function showEditScheduledMessage($params)
	{
		SERIA_Base::pageRequires('admin');
		$message = array_shift($params);
		$sched = array_shift($params);
		$template = new SERIA_MetaTemplate();
		$template->addVariable('messageId', $message);
		$template->addVariable('id', $sched);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/alerter/schedulemessage.php');
		die();
	}
}