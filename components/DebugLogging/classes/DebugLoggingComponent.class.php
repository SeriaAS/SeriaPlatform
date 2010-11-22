<?php

class DebugLoggingComponent extends SERIA_Component
{
		function getId()
		{
			return 'debug_logging_component_jep';
		}
		function getName()
		{
			return _t('Debug logging component');
		}
		function embed()
		{
			SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($this, 'guiEmbed'));
		}
		function getInstallationPath()
		{
			return dirname(dirname(__FILE__));
		}

		public function guiEmbed($gui)
		{
			$gui->addMenuItem('controlpanel/other/debuglogging', _t('Debug logging'), _t('Collects debug logs from page views and sorts them based on which page view they belong to. User interface to look at debug-logs.'), SERIA_HTTP_ROOT.'/seria/components/DebugLogging/', SERIA_HTTP_ROOT.'/seria/components/DebugLogging/icon.png', 100);
			$gui->addMenuItem('controlpanel/other/debuglogging/logs', _t('Debug logs'), _t('Shows saved debug logs.'), SERIA_HTTP_ROOT.'/seria/components/DebugLogging/', SERIA_HTTP_ROOT.'/seria/components/DebugLogging/icon.png', 100);
		}
}
