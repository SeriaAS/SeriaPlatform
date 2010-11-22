<?php
	/**
	*	SERIA_Application class. All applications must extend and create an instance of this object and add it to SERIA_Base::addApplication($app)
	*/

	abstract class SERIA_Application extends SERIA_EventDispatcher implements SERIA_NamedObject
	{
		/**
		*	Dispatched when all applications have been loaded
		*/
		const EMBED_HOOK = 'seria_application_embed';

		// placement of application icons determined by the applications weight
		public $weight = 0;

		// returns a string that uniquely identifies the application. Two applications that are incompatible can never share the unique name
		abstract function getId();

		// returns a string with the name of the application. This string should be translated before it is returned.
		abstract function getName();

		// after all applications have been loaded, the embed() is called for each application
//		abstract function embed();

		// return the absolute path to the directory where the application is installed
		abstract function getInstallationPath();

		// return the absolute http path to where this application is accessed from trough the web server
		abstract function getHttpPath();


		function getObjectId()
		{
			return array("SERIA_Applications","getApplication", $this->getApplicationId());
		}
	}
