<?php

class WindowsLiveAuthproviderComponent extends SERIA_Component
{
		// returns a string that uniquely identifies the component. Two components that are incompatible can never share the unique name
		function getId()
		{
			return 'windows_live_authprovider_component';
		}

		// returns a string with the name of the component. This string should be translated before it is returned.
		function getName()
		{
			return _t('Windows Live authprovider component');
		}

		public /*protected*/ function integrateWithAuthproviders($authproviders)
		{
			SERIA_Authproviders::addProviderClass('WindowsLiveAuthprovider');
		}
		// after all components have been loaded, the embed() is called for each component
		function embed()
		{
			$authproviders = SERIA_Components::getComponent('seria_authproviders');
			if ($authproviders !== false)
				$this->integrateWithAuthproviders($authproviders);
			else
				SERIA_Hooks::listen('Authproviders::inited', array($this, 'integrateWithAuthproviders'));
		}

		// return the absolute path to the directory where the component is installed
		function getInstallationPath()
		{
			return dirname(dirname(__FILE__));
		}

		public function getPrivateCodegenDir()
		{
			return SERIA_PRIV_ROOT.'/'.$this->getId();
		}

		public function getTemplateFilename($templateName)
		{
			if (file_exists(SERIA_ROOT.'/templates/WindowsLiveAuthprovider/'.$templateName.'.php'))
				return SERIA_ROOT.'/templates/WindowsLiveAuthprovider/'.$templateName.'.php';
			else if (file_exists($this->getInstallationPath().'/templates/'.$templateName.'.php'))
				return $this->getInstallationPath().'/templates/'.$templateName.'.php';
			else
				throw new SERIA_Exception('Template not found: '.$templateName);
		}
}
