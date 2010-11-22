<?php
	class SERIA_PrivilegesHooks {
		const EMBED = 'SERIA_PrivilegesHooks::EMBED';
	}

	function SERIA_Privileges_init()
	{
		SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_Privileges/classes/*.class.php');
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, 'SERIA_Privileges_gui');
		SERIA_Hooks::listen(SERIA_PrivilegesHooks::EMBED, 'SERIA_Privileges_privileges');
	}

	function SERIA_Privileges_gui($gui)
	{
		$gui->addMenuItem('controlpanel/users/privileges', _t("Privileges"), _t("Grant and revoke user privileges on this server"), SERIA_HTTP_ROOT.'/seria/components/SERIA_Privileges/pages/');
	}

	function SERIA_Privileges_privileges()
	{
		SERIA_Privileges::registerApplicationPrivileges('SERIA_Privileges', _t("User privileges"), array(
			'access' => array(_t("View user privileges"), _t("The 'View user privileges' privilege grants the user access to view privileges of all users from the control panel application.")),
			'modify' => array(_t("Manage user privileges"), _t("The 'Manage user privileges' privilege grants the user access to edit other users privileges from the control panel application.")),
		));
	}
