<?php

require_once(dirname(__FILE__).'/twitteroauth/twitteroauth.php');

class SERIA_TwitterSys
{
	protected $appkey = null;
	protected $authdata = null;

	protected $toa = null;
	protected $authenticated = false;

	public function __construct($appkey, $authdata=null)
	{
		$this->appkey = $appkey;
		$this->authdata = $authdata;
	}
	protected function init()
	{
		if ($this->authdata === null)
			$this->toa = new TwitterOAuth($this->appkey['key'], $this->appkey['secret']);
		else {
			$this->toa = new TwitterOAuth($this->appkey['key'], $this->appkey['secret'], $this->authdata['key'], $this->authdata['secret']);
			$this->authenticated = $this->authdata['authenticated'];
		}
	}

	public function isAuthenticated()
	{
		if ($this->toa === null && $this->authdata !== null)
			$this->init();
		return $this->authenticated;
	}

	public function authenticate($finalRedirect)
	{
		$this->init();
		$tok = $this->toa->getRequestToken('');
		switch ($this->toa->http_code) {
			case 200:
				/*
				 * Session is required for authentication..
				 */
				if (!session_id())
					session_start();

				$_SESSION['twitter_auth_redirect'] = $finalRedirect;
				$_SESSION['twitter_app_token'] = $this->appkey;
				$_SESSION['twitter_auth_token'] = $tok;

				$url = $this->toa->getAuthorizeURL($tok['oauth_token']);
				header('Location: '.$url);
				die();
				break;
			default:
				throw new Exception('Twitter authentication failed due to connection problems (Http-code: '.$this->toa->http_code.')');
		}
	}

	public function handleReturnFromTwitter()
	{
		if (isset($_REQUEST['oauth_token']) && $_SESSION['twitter_auth_token']['oauth_token'] !== $_REQUEST['oauth_token']) {
			/* Invalid keys */
			throw new Exception('Twitter authentication failed with no apparent reason.');
		}
		$this->appkey = $_SESSION['twitter_app_token'];
		$this->authdata = array(
			'key' => $_SESSION['twitter_auth_token']['oauth_token'],
			'secret' => $_SESSION['twitter_auth_token']['oauth_token_secret'],
			'authenticated' => false
		);
		$this->init();
		$access_token = $this->toa->getAccessToken(isset($_REQUEST['oauth_verifier']) ? $_REQUEST['oauth_verifier'] : false);
		$this->authdata = array(
			'key' => $access_token['oauth_token'],
			'secret' => $access_token['oauth_token_secret'],
			'authenticated' => true
		);
		$finalRedirect = $_SESSION['twitter_auth_redirect'];
		unset($_SESSION['twitter_app_token']);
		unset($_SESSION['twitter_auth_token']);
		switch ($this->toa->http_code) {
			case 200:
				/* The user has been verified and the access tokens can be saved for future use */
				$this->saveToSession();
				header('Location: '.$finalRedirect);
				die();
				break;
			default:
				/* Save HTTP status for error dialog on connnect page.*/
				if (isset($_SESSION['twitter_authentication']))
					unset($_SESSION['twitter_authentication']);
				die('FAILED');
				$this->authdata = null;
				header('Location: '.$finalRedirect);
				die();
		}
	}

	public function saveToSession()
	{
		$_SESSION['twitter_authentication'] = $this->authdata;
	}
	public static function restoreFromSession($appkey)
	{
		if (isset($_SESSION['twitter_authentication']))
			return new self($appkey, $_SESSION['twitter_authentication']);
		else
			return null;
	}

	/*
	 * Normal API
	 */
	public static function getAuth($keys=null)
	{
		$appkey = array(
			'key' => SERIA_TWITTER_TOKEN,
			'secret' => SERIA_TWITTER_TOKEN_SECRET
		);
		if ($keys === null) {
			if (isset($_SESSION['twitter_authentication'])) {
				$auth = $_SESSION['twitter_authentication'];
				return new self($appkey, $auth);
			} else
				return null;
		} else
			return new self($appkey, $keys);
	}
	public static function startAuth($redirectTo=null)
	{
		$appkey = array(
			'key' => SERIA_TWITTER_TOKEN,
			'secret' => SERIA_TWITTER_TOKEN_SECRET
		);
		$authsys = new self($appkey);
		$authsys->authenticate($redirectTo);
	}
	public function getKeys()
	{
		return $this->authdata;
	}

	public function signedRequest($url, $method, $parameters=array())
	{
		if ($this->toa === null)
			$this->init();
		if (!$this->isAuthenticated())
			throw new Exception('Not authenticated.');
		$result = $this->toa->oAuthRequest($url, $method, $parameters);
		if ($this->toa->http_code == 200)
			return $result;
		else
			throw new Exception('Twitter http-error: '.$this->toa->http_code);
	}
}

?>