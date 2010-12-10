<?php

class NDLA_RoamAuthCookieComponent extends SERIA_Component
{
	protected $domain;

	public function __construct($domain)
	{
		$this->domain = $domain;
	}

	function getId()
	{
		return 'ndla_roam_auth_cookie_component';
	}
	function getName()
	{
		return _t('NDLA Roam Auth Cookie');
	}
	function embed()
	{
		SERIA_Hooks::listen(SERIA_Base::LOGGED_OUT, array($this, 'userSet'));
		SERIA_Hooks::listen(SERIA_Base::LOGGED_IN, array($this, 'userSet'));
	}
	function getInstallationPath()
	{
		return dirname(dirname(__FILE__));
	}

	public function userSet($usr)
	{
		$user = SERIA_Base::user();
		if ($user !== false) {
			$roamUrl = RoamAuth::getRoamAuthParamValue();
			if ($roamUrl)
				$this->setRoamAuthCookie($roamUrl);
		} else
			$this->setRoamAuthCookie(null);
	}
	protected function setRoamAuthCookie($url)
	{
		if ($url !== null) {
			if (!setCookie('NdlaRoamAuth', $url, time() + SERIA_SESSION_TTL * 100, '/', $this->domain, false, false))
				throw new SERIA_Exception('Failed to set roam auth cookie!');
		} else {
			if (!setCookie('NdlaRoamAuth', '', time() - 3600, '/', $this->domain, false, false))
				throw new SERIA_Exception('Failed to set roam auth cookie!');
		}
		if (!setCookie('NdlaRoamAuthUpdate', sha1(mt_rand().mt_rand().mt_rand().mt_rand().time()), time() + SERIA_SESSION_TTL * 100, '/', $this->domain, false, false))
			throw new SERIA_Exception('Failed to set roam auth update-cookie!');
	}
}