<?php

class SERIA_TranslationApplication extends SERIA_Application
{
	private $articleTypes = array();

	function getId() { return 'seria_translation'; }
	function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/apps/translation'; }
	function getInstallationPath() { return dirname(dirname(__FILE__)); }
	function getName() { return _t('Seria Translation'); }

	// Add event listeners and hook into wherever
	function embed()
	{
		if (SERIA_TRANSLATION_APPLICATION_ENABLE != 0)
			SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($this, 'guiEmbed'));
	}

	private $active = false;
	public function setActive($active)
	{
		$this->active = $active;
	}

	// hook for adding icon to the user interface
	function guiEmbed($gui)
	{
		$gui->addMenuItem('controlpanel/other/translation', $this->getName(), _t("Edit translations"), $this->getHttpPath(), $this->getHttpPath().'/icon.png');
	}
}

?>
