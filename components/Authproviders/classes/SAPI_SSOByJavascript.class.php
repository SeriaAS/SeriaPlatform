<?php

class SAPI_SSOByJavascript extends SAPI
{
	/**
	 * @param $siteType Currently only supported is 'drupal'.
	 * @return string A script that checks for a valid login at this site. The script itself must be completely stateless except hostnames and urls for checking by jsonp.
	 */
	public static function activationScript($siteType)
	{
		ob_start();
		require(dirname(__FILE__).'/../js/ssobyjs.js.php');
		return ob_get_clean();
	}
	public static function ssoCheck()
	{
		$sid = session_id();
		if ($sid && SERIA_Base::user()) {
			/*
			 * Make sure that the XML-file exists and is up to date..
			 */
			new SERIA_UserLoginXml($sid, SERIA_Base::user());
			$hostname = SERIA_Url::current()->getHost();
			return array(
				'loggedIn' => true,
				'userXml' => SERIA_UserLoginXml::getUserXmlUrl($sid),
				'userChange' => sha1('SAPI_SSOByJavascript:'.SERIA_Base::user()->get('id').'@'.$hostname)
			);
		} else
			return array('loggedIn' => false);
	}
}