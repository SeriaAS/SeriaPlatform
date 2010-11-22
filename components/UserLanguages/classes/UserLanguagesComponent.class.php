<?php

class UserLanguagesComponent extends SERIA_Component
{
	function getId()
	{
		return 'UserLanguagesComponent';
	}
	function getName()
	{
		return _t('User languages component');
	}
	function embed()
	{
		SERIA_Router::instance()->addRoute('UserLanguagesComponent', 'Configure languages', array('UserLanguagesComponent', 'showConfigureLanguages'), 'userlanguages/configure');
		SERIA_Router::instance()->addRoute('UserLanguagesComponent', 'Add language', array('UserLanguagesComponent', 'showAddLanguage'), 'userlanguages/add');
		SERIA_Router::instance()->addRoute('UserLanguagesComponent', 'Add custom language', array('UserLanguagesComponent', 'showAddCustomLanguage'), 'userlanguages/addcustom');
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	public function guiEmbed(SERIA_Gui $gui)
	{
		$gui->addMenuItem('controlpanel/users/languages', _t('User languages'), _t('Set available languages for this site.'), SERIA_HTTP_ROOT.'?route=userlanguages/configure', $this->getInstallationPath().'/icon.png', 100);
	}

	public function showConfigureLanguages()
	{
		$template = new SERIA_MetaTemplate();
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/userlanguages.php');
		die();
	}
	public function showAddLanguage()
	{
		$template = new SERIA_MetaTemplate();
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/addlanguage.php');
		die();
	}
	public function showAddCustomLanguage()
	{
		$template = new SERIA_MetaTemplate();
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/addcustom.php');
		die();
	}
}