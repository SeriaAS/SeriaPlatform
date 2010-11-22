<?php
	SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, 'seria_controlpanel_usermanagement_embed');
	function seria_controlpanel_usermanagement_embed($gui) {
		$gui->addMenuItem('controlpanel/users', _t("User management"), _t("Manage system user accounts and rights"), SERIA_HTTP_ROOT."/seria/apps/controlpanel/components/usermanagement/", SERIA_HTTP_ROOT."/seria/apps/controlpanel/icon.png");
		$gui->addMenuItem('controlpanel/users/list', _t("List user accounts"), _t("Display all user accounts."), SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/usermanagement/index.php');
		$gui->addMenuItem('controlpanel/users/edit', _t("Create user"), _t("Create a user account."), SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/usermanagement/user.php');
	}
