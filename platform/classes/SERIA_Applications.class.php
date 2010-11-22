<?php
	class SERIA_Applications
	{
		/**
		 *       Register a application for the framework.
		 */
		static function addApplication(SERIA_Application $app)
		{
			$GLOBALS["seria"]["applications"][$app->getId()] = $app;
		}

		/**
		*	Get a list of all applications loaded (array of objects).
		*/
		static function getApplications()
		{
			return $GLOBALS["seria"]["applications"];
		}

		/**
		*	Get a specific application object, if it is available. Returns FALSE if not.
		*/
		static function getApplication($name)
		{
			if(isset($GLOBALS["seria"]["applications"][$name]))
				return $GLOBALS["seria"]["applications"][$name];
			return false;
		}
	}
