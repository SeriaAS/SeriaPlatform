<?php
	class SERIA_CloudService extends SERIA_Service
	{
		public static function getProvider() {
			throw new SERIA_Exception('Not configured', SERIA_Service::NOT_CONFIGURED_EXCEPTION);
		}

		public static function getProviders() {
			$providers = SERIA_Hooks::dispatch(SERIA_CloudServiceHooks::GET_PROVIDERS);
			return array(self::getProvider());
		}

		public static function guiEmbed($gui) {
			$gui->addMenuItem('controlpanel/settings/services/cloud', _t('Cloud'), _t('Cloud Administration'), SERIA_HTTP_ROOT.'/seria/components/SERIA_CloudService/', SERIA_HTTP_ROOT.'/seria/components/SERIA_CloudService/icon.png', 100);
		}
	}
