<?php
	SERIA_Hooks::listen(SERIA_GuiManifest::EMBED_HOOK, 'seria_controlpanel_rpc_embed');
	function seria_controlpanel_rpc_embed($gui) {
		$gui->addMenuItem('controlpanel/settings/rpc', _t('RPC'), _t('Edit RPC settings for local and remote services'), SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/rpc/', SERIA_HTTP_ROOT."/seria/apps/controlpanel/icon.png");
		$gui->addMenuItem('controlpanel/settings/rpc/services', _t('RPC services'), _t('Edit RPC settings for remote services'), SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/rpc/services.php');
		$gui->addMenuItem('controlpanel/settings/rpc/accounts', _t('RPC accounts'), _t('Edit RPC settings for exported services'), SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/rpc/accounts.php');
		$gui->addMenuItem('controlpanel/settings/rpc/accounts/new', _t('New RPC account'), _t('Create a new authentication key for exported services'), SERIA_HTTP_ROOT.'/seria/apps/controlpanel/components/rpc/accounts.php?id=create');
//		$gui->addMenuItem('controlpanel/settings/rpc/accounts/edit', _t('Edit RPC account: %NAME%', array('NAME' => $record->get('name'))), _t('Edit the authentication key for an exported service'), $app->getHttpPath().'/rpc/accounts.php?id='.urlencode($_GET['id']), $app->getHttpPath().'/icon.png');

	}
