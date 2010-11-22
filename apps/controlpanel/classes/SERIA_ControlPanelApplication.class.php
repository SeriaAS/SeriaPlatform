<?php

	class SERIA_ControlPanelApplication extends SERIA_Application
	{
		private $active = false;

		function getId() { return 'seria_controlpanel'; }
		function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/apps/controlpanel'; }
		function getInstallationPath() { return dirname(dirname(__FILE__)); }
		function getName() { return _t('Control Panel'); }

		function __construct()
		{
		}

		function setActive($state)
		{
			$this->active = $state;
		}

		function guiEmbed($gui)
		{
			$gui->addMenuItem('controlpanel', $this->getName(), _t("Customize the functionality of your website"), $this->getHttpPath(), $this->getHttpPath().'/icon.png');
			$gui->addMenuItem('controlpanel/settings', _t("Settings"), _t("Seria Platform configuration"), $this->getHttpPath().'/settings.php',$this->getHttpPath().'/icon.png'); 
			$gui->addMenuItem('controlpanel/other', _t('Other'), _t('Other configuration options'), $this->getHttpPath().'/other.php', $this->getHttpPath().'/icon.png');
		}
	}
