<?php

	class SERIA_LiveApplication extends SERIA_Application implements SERIA_RPCServer
	{
		private $active = false;

		static function rpc_getArticle($articleId)
		{
			$a = SERIA_Article::createObjectFromId($articleId);
			$res = array(
				'id' => $a->get('id'),
				'stream_url' => 'http://stream.serialive.com',
				'stream_point1' => $a->get('stream_point1'),
				'stream_point2' => $a->get('stream_point2'),
				'publish_point1' => $a->get('publish_point1'),
				'publish_point2' => $a->get('publish_point2'),
			);
			return $res;
		}

		function setActive($state)
		{
			$this->active = $state;
		}

		// returns a string that uniquely identifies the application. Two applications that are incompatible can never share the unique name
		function getId() { return 'seria_live'; }
		function getInstallationPath() { return dirname(dirname(__FILE__)); }
		function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/apps/live/'; }

		function getName() { return _t('Seria Live');}

		function getObjectId()
		{
			return array("SERIA_Applications","getApplication", $this->getApplicationId());
		}

		function __construct()
		{

		}


                // after all applications have been loaded, the embed() is called for each application
                function embed()
                {
//			SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($this, 'guiEmbed'));
 //                       SERIA_Hooks::listen('seria_router', array($this, 'router'), -100);
                }

		function guiEmbed($gui)
		{
			$gui->addMenuItem('live', $this->getName(), _t('Use Seria Live to publish live content on your website.'), SERIA_HTTP_ROOT.'/seria/apps/live/', SERIA_HTTP_ROOT.'/seria/apps/live/icon.png', 100);
/*
			$gui->addMenuItem('live', $this->getName(), _t('Use Seria Live to publish live/recorded video to your website.'), SERIA_HTTP_ROOT.'/seria/apps/live/', SERIA_HTTP_ROOT.'/seria/apps/live/icon.png', 100);
			$gui->addMenuItem('live/events', _t("Event Management"), _t("Upcoming events."), SERIA_HTTP_ROOT.'/seria/apps/live/upcoming.php');
			$gui->addMenuItem('live/statistics', _t("Statistics"), _t("Seria Live settings for your account."), SERIA_HTTP_ROOT.'/seria/apps/live/statistics.php');
			$gui->addMenuItem('live/settings', _t("Settings"), _t("Seria Live settings for your account."), SERIA_HTTP_ROOT.'/seria/apps/live/settings.php');

			$gui->addMenuItem('live/events/upcoming', _t("Upcoming events"), _t("Upcoming events"), SERIA_HTTP_ROOT.'/seria/apps/live/upcoming.php');
			$gui->addMenuItem('live/events/archive', _t("Archived events"), _t("Archived events"), SERIA_HTTP_ROOT.'/seria/apps/live/archive.php');
			if(SERIA_Base::hasRight("create_live_event")) {
				$gui->addMenuItem('live/events/edit', _t("Create new presentation"), _t("Create a new live presentation."), SERIA_HTTP_ROOT.'/seria/apps/live/edit.php');
			}
*/
		}

/*		function embed()
		{
			$publisher = SERIA_Applications::getApplication('seria_publisher');
			if ($publisher)
				$publisher->addArticleType('SERIA_Live');
			SERIA_Hooks::listen('seria_gui_application_icons', array($this, 'applicationIcon'));
		}
*/
		function applicationIcon()
		{
			return array(
				'url' => SERIA_HTTP_ROOT.'/seria/apps/live/',
				'icon' => SERIA_HTTP_ROOT.'/seria/apps/live/icon.png',
				'caption' => $this->getName(),
				'active' => $this->active,
			);
		}
	}
