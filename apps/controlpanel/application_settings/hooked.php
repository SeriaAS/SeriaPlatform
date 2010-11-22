<?php
	require('../common.php');
	SERIA_Base::pageRequires("admin");
	SERIA_Base::viewMode("admin");

	$icons = SERIA_Hooks::dispatch('seria_controlpanel_settings_icons');

	if (isset($_GET['icon']) && isset($_GET['hash'])) {
		if (isset($icons[$_GET['icon']]) && isset($icons[$_GET['icon']]['callback']) && hash('md4', serialize($icons[$_GET['icon']])) == $_GET['hash']) {
			$gui->contents(call_user_func($icons[$_GET['icon']]['callback']));
			require(dirname(__FILE__).'/settings_menu.php');
			echo $gui->output();
			die();
		}
		foreach ($icons as $icon) {
			if (isset($icon['callback']) && hash('md4', serialize($icon)) == $_GET['hash']) {
				$gui->contents(call_user_func($icon['callback']));
				require(dirname(__FILE__).'/settings_menu.php');
				echo $gui->output();
				die();
			}
		}
	}
	throw new SERIA_Exception('Unauthorized hook choise');