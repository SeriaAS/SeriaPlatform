<?php


class RoamAuth
{
	protected static $roamAuthKey = 'roamAuthUrl';

	/**
	*       Returns roamAuthUrl=http:/werwerwerwerwer
	*/
	public static function getRoamAuthParam()
	{
		return self::getRoamAuthParamName()."=".rawurlencode(self::getRoamAuthParamValue());
	}

	public /*package*/ static function _getRoamAuthUrl()
	{
		return isset($_GET[self::$roamAuthKey]) && !empty($_GET[self::$roamAuthKey]) ? $_GET[self::$roamAuthKey] : false;
	}

	public static function getRoamAuthParamName()
	{
		return self::$roamAuthKey;
	}

	public static function getRoamAuthParamValue()
	{
		if (SERIA_Base::user() !== false) {
			if (isset($_SESSION['AUTHPROVIDERS_REMOTE_XML']) &&
			    isset($_SESSION['AUTHPROVIDERS_USER_XML_FILE']) &&
			    file_exists($_SESSION['AUTHPROVIDERS_USER_XML_FILE']))
				return $_SESSION['AUTHPROVIDERS_REMOTE_XML'];
			$component = SERIA_Components::getComponent('seria_authproviders');
			if ($component && $component->isEnabled()) {
				$sid = session_id();
				if (!$sid)
					return false;
				$_SESSION['AUTHPROVIDERS_SID'] = array(time(), session_id(), SERIA_Base::user()->get('id'), $sid);
				new SERIA_UserLoginXml($sid, SERIA_Base::user());
				$_SESSION['AUTHPROVIDERS_USER_XML_FILE'] = SERIA_UserLoginXml::getUserXmlFilename($sid);
				$_SESSION['AUTHPROVIDERS_REMOTE_XML'] = SERIA_UserLoginXml::getUserXmlUrl($sid);
				return $_SESSION['AUTHPROVIDERS_REMOTE_XML'];
			}
			return false;
		}
	}


	public static function createRoamingUrl(SERIA_Url $url)
	{
		$url->setParam(self::getRoamAuthParamName(), self::getRoamAuthParamValue());
		return $url;
	}

	public static function getRoamAuthData()
	{
		$roam = self::_getRoamAuthUrl();
		$xmlData = file_get_contents($roam);
		if ($xmlData)
			return SERIA_UserLoginXml::parseXml($xmlData);
		return false;
	}
}

