<?php

if (defined('NDLA_ROAM_AUTH_COOKIE') && NDLA_ROAM_AUTH_COOKIE) {
	/**
	 *
	 *
	 * @author Jan-Espen Pettersen
	 * @package authndla
	 *
	 */
	class NDLA_RoamAuthCookieManifest
	{
		const SERIAL = 1;
		const NAME = 'NDLA_RoamAuthCookie';
	
		public static $classPaths = array(
			'classes/*.class.php'
		);
		public static $dependencies = array(
			'RoamAuthprovider'
		);
	}

	function NDLA_RoamAuthCookieInit()
	{
		$component = new NDLA_RoamAuthCookieComponent(NDLA_ROAM_AUTH_COOKIE);
		SERIA_Components::addComponent($component);
		$component->embed();
	}
}