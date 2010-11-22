<?php
	class SERIA_Services
	{
		public static function guiEmbed(SERIA_Gui $gui)
		{
			$gui->addMenuItem('controlpanel/settings/services', _t("Services"), _t("Manage Services"), SERIA_HTTP_ROOT.'/seria/components/SERIA_Services/', SERIA_HTTP_ROOT.'/seria/components/SERIA_Services/icon.png');
		}
	}
