<?php
	class SERIA_Components
	{
                /**
                *       Register a component for the framework.
                */
                static function addComponent(SERIA_Component $app)
                {
                        $GLOBALS["seria"]["components"][$app->getId()] = $app;
                }

		/**
		*	Get a list of all components loaded (array of objects).
		*/
		static function getComponents()
		{
			return $GLOBALS["seria"]["components"];
		}

		/**
		*	Get a specific component object, if it is available. Returns FALSE if not.
		*/
		static function getComponent($name)
		{
			if(isset($GLOBALS["seria"]["components"][$name]))
				return $GLOBALS["seria"]["components"][$name];
			return false;
		}
	}
