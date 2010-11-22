<?php

class UserRolesComponent extends SERIA_Component
{
		// returns a string that uniquely identifies the component. Two components that are incompatible can never share the unique name
		function getId()
		{
			return 'user_roles_component';
		}

		// returns a string with the name of the component. This string should be translated before it is returned.
		function getName()
		{
			return _t('User roles component');
		}

		// after all components have been loaded, the embed() is called for each component
		function embed()
		{
		}

		// return the absolute path to the directory where the component is installed
		function getInstallationPath()
		{
			return SERIA_ROOT.'/seria/components/UserRoles';
		}
}
