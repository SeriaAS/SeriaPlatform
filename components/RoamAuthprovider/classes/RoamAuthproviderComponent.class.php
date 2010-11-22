<?php

class RoamAuthproviderComponent extends SERIA_Component
{
		function getId()
		{
			return 'roamauthcomponent';
		}

		function getName()
		{
			return _t('Roaming authentication provider');
		}

		public function integrateWithAuthproviders($component)
		{
			SERIA_Authproviders::addProviderClass('RoamAuthprovider');
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
	}