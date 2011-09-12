<?php

class SimplesamlAuthproviderComponent extends SERIA_Component
{
		function getId()
		{
			return 'simplesamlcomponent';
		}

		function getName()
		{
			return _t('Simplesaml authentication provider');
		}

		public function integrateWithAuthproviders($component)
		{
			SERIA_Authproviders::addProviderClass('FeideAuthprovider');
			SERIA_Authproviders::addProviderClass('OpenidGoogleAuthprovider');
			SERIA_Authproviders::addProviderClass('TwitterAuthprovider');
		}

		function embed()
		{
			$authproviders = SERIA_Components::getComponent('seria_authproviders');
			if ($authproviders !== false)
				$this->integrateWithAuthproviders($authproviders);
			else
				SERIA_Hooks::listen('Authproviders::inited', array($this, 'integrateWithAuthproviders'));
		}

		function getInstallationPath()
		{
			return dirname(dirname(__FILE__));
		}

		public function getTemplateFilename($templateName)
		{
			if (file_exists(SERIA_ROOT.'/templates/SimplesamlAuthprovider/'.$templateName.'.php'))
				return SERIA_ROOT.'/templates/SimplesamlAuthprovider/'.$templateName.'.php';
			else if (file_exists($this->getInstallationPath().'/templates/'.$templateName.'.php'))
				return $this->getInstallationPath().'/templates/'.$templateName.'.php';
			else
				throw new SERIA_Exception('Template not found: '.$templateName);
		}
		public function parseTemplate($templateName, $params)
		{
			$filename = $this->getTemplateFilename($templateName);
			SERIA_Template::parse($filename, $params);
		}
		public function parseTemplateToString($templateName, $params)
		{
			$filename = $this->getTemplateFilename($templateName);
			return SERIA_Template::parseToString($filename, $params);
		}
}
