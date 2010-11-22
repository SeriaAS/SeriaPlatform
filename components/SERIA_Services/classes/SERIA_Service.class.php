<?php
	/**
	*	Provides a common interface for all types of Services available as APIs in Seria Platform
	*/
	abstract class SERIA_Service
	{
		/**
		*	Exception error code when the service has not been configured by the administrator
		*/
		const NOT_CONFIGURED_EXCEPTION = 'NOT_CONFIGURED_EXCEPTION';

		/**
		*	Returns the default SERIA_ServiceProvider object as configured in the Control Panel.
		*	@return SERIA_ServiceProvider
		*/
		abstract public static function getProvider();

		/**
		*	Returns an array containing all available SERIA_ServiceProvider objects as configured in the Control Panel.
		*	@return array
		*/
		abstract public static function getProviders();
	}
