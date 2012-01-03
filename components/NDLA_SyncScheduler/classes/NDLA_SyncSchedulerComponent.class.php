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
		SERIA_Router::instance()->addRoute('NDLA_SyncSchedulerComponent', 'Sync scheduler API', array($this, 'apiRouter'), 'ndlasyncschedules/api');
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

	/**
	 *
	 * Call NDLA_SyncScheduler api directly.
	 * @param string $api API-path
	 * @param array $params Array of parameters (like $_GET).
	 * @param array $post Array of post-data (like $_POST).
	 * @throws SERIA_Exception
	 * @return array Result data.
	 */
	public function api($api, $params, $post)
	{
		$api = str_replace('\\', '/', $api);
		$api = 'api/'.$api;
		$dir = dirname(dirname(__FILE__));
		$api = explode('/', $api);
		while ($api) {
			$dh = opendir($dir);
			$nodes = array();
			while (($node = readdir($dh)))
				$nodes[] = $node;
			closedir($dh);
			$part = array_shift($api);
			if (in_array($part, $nodes))
				$dir .= '/'.$part;
			else if (in_array($part.'.php', $nodes))
				$dir .= '/'.$part.'.php';
			else
				throw new SERIA_Exception('Not found: '.$part, SERIA_Exception::NOT_FOUND);
			if (!is_dir($dir))
				break;
		}
		$path = implode('/', $api);
		/*
		 * Varaibles in current scope:
		 * $path - The path after matched file sync.php/path/whatever.. (=path/wha...)
		 * $params - $_GET-parameters that are available to the api-file.
		 * $post - $_POST-data.
		 *
		 * DO NOT USE $_GET or $_POST in the api-files.
		 */
		$result = require($dir);
		return $result;
	}

	public function apiRouter()
	{
		if (isset($_GET['api']) && $_GET['api']) {
			$params = $_GET;
			unset($params['api']);
			if (isset($params['route']))
				unset($params['route']);
			$result = $this->api($_GET['api'], $params, $_POST);
			die(SERIA_Lib::toJSON($result));
		} else
			throw new SERIA_Exception('API requires params: api');
		die();
	}
}
