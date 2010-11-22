<?php
	/**
	*	SERIA_Component class. All components must extend and create an instance of this object and add it to SERIA_Base::addComponent($app)
	*/

	abstract class SERIA_Component extends SERIA_EventDispatcher implements SERIA_NamedObject
	{
		// returns a string that uniquely identifies the component. Two components that are incompatible can never share the unique name
		abstract function getId();

		// returns a string with the name of the component. This string should be translated before it is returned.
		abstract function getName();

		// after all components have been loaded, the embed() is called for each component
		abstract function embed();

		// return the absolute path to the directory where the component is installed
		abstract function getInstallationPath();

		function getObjectId()
		{
			return array("SERIA_Components","getComponent", $this->getComponentId());
		}
	}
