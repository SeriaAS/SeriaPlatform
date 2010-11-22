<?php
/**
*	This component provides convertion of ppt-files to a list of png-files trough a hosted service from
*	Seria.
*/
//require(dirname(__FILE__).'/classes/PowerpointConverter.class.php');
SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/PowerpointConverter/classes/*.class.php');

SERIA_Hooks::listen('SERIA_File::convertTo::ppt2png', array('PowerpointConverter', 'convertPPTtoPNG'));

SERIA_Router::instance()->addRoute('PowerpointConverter', 'Show powerpoint conversion status', array('PowerpointConverter', 'showStatus'), 'powerpointconverter/status');

function PowerpointConverter_addMenu($gui)
{
	$gui->addMenuItem('controlpanel/other/powerpoint', _t('Powerpoint conversion status'), _t('Show Powerpoint conversion status.'), SERIA_HTTP_ROOT.'?route=powerpointconverter/status', SERIA_HTTP_ROOT.'/seria/components/PowerpointConverter/icon.png', 100);
	$gui->addMenuItem('controlpanel/other/powerpoint/status', _t('Status'), _t('Show Powerpoint conversion status.'), SERIA_HTTP_ROOT.'?route=powerpointconverter/status', SERIA_HTTP_ROOT.'/seria/components/PowerpointConverter/icon.png', 100);
}

function PowerpointConverter_init()
{
	SERIA_Hooks::listen(SERIA_Gui::EMBED_HOOK, 'PowerpointConverter_addMenu');
}
