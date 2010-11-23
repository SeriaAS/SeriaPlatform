<?php

class SERIA_FlashPlayerComponent extends SERIA_Component
{
	function getId()
	{
		return 'SERIA_FlashPlayerComponent';
	}
	function getName()
	{
		return _t('SERIA_FlashPlayer component');
	}
	function embed()
	{
		SERIA_Router::instance()->addRoute('SERIA_FlashPlayerComponent', 'Configure SERIA_FlashPlayer', array('SERIA_FlashPlayerComponent', 'showConfigureSERIA_FlashPlayer'), 'SERIA_FlashPlayer/configure');
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	public function guiEmbed(SERIA_Gui $gui)
	{
		$gui->addMenuItem('controlpanel/other/SERIA_FlashPlayer', _t('Flash Player Configuration'), _t('Configure Flash Player for users.'), SERIA_HTTP_ROOT.'?route=SERIA_FlashPlayer/configure', $this->getInstallationPath().'/icon.png', 100);
	}

	public function showConfigureSeriaFlashPlayer()
	{
		$template = new SERIA_MetaTemplate();
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/configure.php');
		die();
	}
}
