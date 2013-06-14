<?php

class SERIA_ExternalReq2ReturnPost
{
	const HOST_ACCESS_CONSENT_HOOK = 'SERIA_ExternalReq2ReturnPost::HOST_ACCESS_CONSENT_HOOK';

	protected $url;
	protected $user;
	protected $returnData;

	public function __construct($url, $user, $returnData=null)
	{
		$this->url = $url;
		$this->user = $user;
		$this->returnData = $returnData;
	}

	public function getPostUrl()
	{
		return $this->url;
	}
	public function getPostData()
	{
		$requestToken = SERIA_ExternalReq2Token::createToken(array('uid' => $this->user->get('id')))->__toString();
		$data = array(
			'loggedIn' => 1,
			'openSessionToken' => $requestToken,
			'roamAuthUrl' => SERIA_UserLoginXml::getUserXmlUrl(session_id())
		);
		if ($this->returnData !== null)
			$data['returnData'] = $this->returnData;
		return $data;
	}

	public function getHostname()
	{
		$url = new SERIA_Url($this->url);
		return $url->getHost();
	}

	/**
	 *
	 * This method checks whether we are allowed to send information to
	 * the requesting server. If data can be sent right away it returns
	 * true, and if data can not be sent it returns false. But if a
	 * consent-request is shown to the user this method does not return
	 * at all (shows a consent-req-page and dies). 
	 */
	public function hostAccessConsentCheck($abortUrl)
	{
		$checkHost = $this->getHostname();
		if (defined('SERIA_AUTHPROVIDERS_EXTERNAL_HOSTS_USER_ACCESS')) {
			$allowed = SERIA_AUTHPROVIDERS_EXTERNAL_HOSTS_USER_ACCESS;
			$allowed = explode(',', $allowed);
			foreach ($allowed as $host) {
				$host = trim($host);
				if ($host) {
					if ($host[0] == '.') {
						if (strlen($checkHost) > strlen($host)) {
							$ending = substr($checkHost, -strlen($host));
							if ($ending == $host)
								return true;
						}
					} else {
						if ($host == $checkHost)
							return true;
					}
				}
			}
		}

		/*
		 * Normally the consent requests will display a consent-request-page and
		 * die. So this method may not return at all.
		 */
		$returns = SERIA_Hooks::dispatch(self::HOST_ACCESS_CONSENT_HOOK, $this, $abortUrl);

		/*
		 * Any hook that return true cause a consent to be assumed..
		 */
		foreach ($returns as $ret) {
			if ($ret)
				return true;
		}
		return false;
	}
}