<?php

if (file_exists(SERIA_ROOT.'/_config.outboard.php'))
	require(SERIA_ROOT.'/_config.outboard.php');
else
	return;

SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');

SERIA_Router::instance()->addRoute('outboard', 'Manage alerts', array('SERIA_OutboardApplication', 'showAlerter'), 'outboard/alerter');
SERIA_Router::instance()->addRoute('outboard', 'Add alert message', array('SERIA_OutboardApplication', 'showMessage'), 'outboard/message');
SERIA_Router::instance()->addRoute('outboard', 'Add alert channel', array('SERIA_OutboardApplication', 'showChannel'), 'outboard/channel');
SERIA_Router::instance()->addRoute('outboard', 'Edit alert message', array('SERIA_OutboardApplication', 'showEditMessage'), 'outboard/message/:id');
SERIA_Router::instance()->addRoute('outboard', 'Edit alert channel', array('SERIA_OutboardApplication', 'showEditChannel'), 'outboard/channel/:id');
SERIA_Router::instance()->addRoute('outboard', 'Edit message schedule', array('SERIA_OutboardApplication', 'showSchedule'), 'outboard/schedule/:id');
SERIA_Router::instance()->addRoute('outboard', 'Add scheduled message', array('SERIA_OutboardApplication', 'showAddScheduledMessage'), 'outboard/schedule/:message/message');
SERIA_Router::instance()->addRoute('outboard', 'Edit scheduled message', array('SERIA_OutboardApplication', 'showEditScheduledMessage'), 'outboard/schedule/:message/message/:id');

function outboard_guiembed($gui)
{
	$gui->addMenuItem('outboard', _t('Outboard application'), _t('Outboard application'), SERIA_HTTP_ROOT.'?route=outboard/alerter', SERIA_HTTP_ROOT.'/seria/apps/outboard/icon.png', 100);
	$gui->addMenuItem('outboard/alerter', _t('Alerts'), _t('Alerts'), SERIA_HTTP_ROOT.'?route=outboard/alerter', SERIA_HTTP_ROOT.'/seria/apps/outboard/icon.png', 100);
}

SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, 'outboard_guiembed');
SERIA_Hooks::listen(SERIA_MAINTAIN_HOOK, array('SERIA_AlertGenerator', 'generate'));
