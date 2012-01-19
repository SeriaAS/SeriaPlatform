<?php

class NDLA_SyncSchedulerComponent extends SERIA_Component
{
	function getId()
	{
		return 'NDLA_SyncSchedulerComponent';
	}
	function getName()
	{
		return _t('NDLA Sync Scheduler');
	}
	function embed()
	{
		SERIA_Hooks::listen(SERIA_MAINTAIN_HOOK, array($this, 'maintain'));
		SERIA_Router::instance()->addRoute('NDLA_SyncSchedulerComponent', 'Schedule weekly and additional syncs', array($this, 'editSyncSchedules'), 'ndlasyncschedules/edit');
		SERIA_Router::instance()->addRoute('NDLA_SyncSchedulerComponent', 'Add sync', array($this, 'addSync'), 'ndlasyncschedules/add');
		SERIA_Router::instance()->addRoute('NDLA_SyncSchedulerComponent', 'Edit sync', array($this, 'editSync'), 'ndlasyncschedules/edit/:id');
		SERIA_Router::instance()->addRoute('NDLA_SyncSchedulerComponent', 'Show sync log', array($this, 'showLog'), 'ndlasyncschedules/log');
		SERIA_Router::instance()->addRoute('NDLA_SyncSchedulerComponent', 'Sync now', array($this, 'syncNow'), 'ndlasyncschedules/sync');
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	public function init()
	{
		SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, array($this, 'guiEmbed'));
	}

	/**
	 *
	 * Callback when SERIA_Gui requests set-up. Please don't call.
	 * @param SERIA_Gui $gui
	 */
	public function guiEmbed(SERIA_Gui $gui)
	{
		$gui->addMenuItem('controlpanel/settings/ndlasyncschedule', _t('NDLA Sync Schedules'), _t('Edit the Sync Schedule.'), SERIA_HTTP_ROOT.'?route=ndlasyncschedules/edit', $this->getInstallationPath().'/icon.png', 100);
	}

	/**
	 *
	 * Callback that runs at every maintain.
	 */
	public function maintain()
	{
		NDLA_SyncLog::pollAutomaticSyncs();
	}

	/**
	 *
	 * Callback from router to show the schedules page.
	 */
	public function editSyncSchedules()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		echo $template->parse($this->getInstallationPath().'/pages/schedules.php');
		die();
	}
	/**
	 *
	 * Callback from router to show the add sync page.
	 */
	public function addSync()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		$template->addVariable('sync', false);
		echo $template->parse($this->getInstallationPath().'/pages/addsync.php');
		die();
	}
	/**
	 *
	 * Callback from router to show the edit sync page.
	 */
	public function editSync($params)
	{
		SERIA_Base::pageRequires('admin');
		$sync = SERIA_Meta::load('NDLA_ScheduledSync', $params['id']);
		$template = new SERIA_MetaTemplate();
		$template->addVariable('sync', $sync);
		echo $template->parse($this->getInstallationPath().'/pages/addsync.php');
		die();
	}
	/**
	 *
	 * Callback from router to show the log page.
	 */
	public function showLog()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		echo $template->parse($this->getInstallationPath().'/pages/log.php');
		die();
	}
	/**
	 *
	 * Callback from router to show the sync now page.
	 */
	public function syncNow()
	{
		SERIA_Base::pageRequires('admin');
		$template = new SERIA_MetaTemplate();
		echo $template->parse($this->getInstallationPath().'/pages/syncnow.php');
		die();
	}
}