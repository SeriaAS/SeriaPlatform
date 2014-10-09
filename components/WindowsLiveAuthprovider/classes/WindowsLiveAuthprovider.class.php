<?php

class WindowsLiveAuthprovider extends SERIA_GenericAuthprovider
{
	protected static $provider = null;

	public static function loadProviders()
	{
		$retv = parent::loadProviders();
		self::$provider = new WindowsLiveAuthprovider();
		SERIA_Authproviders::addProvider(self::$provider);
		return array_merge($retv, array(
			'configure' => array(
				'caption' => _t('Configure Windows Live ID SSO'),
				'call' => array('WindowsLiveAuthprovider', 'configureProvider'),
				'version' => 2
			)
		));
	}

	public static function &getProvider()
	{
		if (self::$provider === null)
			SERIA_Authproviders::loadProviders('WindowsLiveAuthprovider');
		return self::$provider;
	}

	public function getProviderId()
	{
		return 'windows_live_authprovider';
	}
	public function getName()
	{
		return _t('Windows Live authprovider');
	}

	public function getParameters()
	{
		return array(
			'attributes' => array(
				'defaults' => array(
				),
				'load' => array(
					'unique' => array('uid', 0),
				),
				'combinations' => array(
				),
				'fillIn' => array(
					/*
					 * Fill in these fields into the SERIA_User object
					 */
					'unique'
				)
			),
			'safeEmail' => false
		);
	}

	public function setWindowsLiveSdkDirectory($dir)
	{
		SERIA_Base::setParam($this->getProviderId().'.sdkDirectory', $dir);
		$xml = new WindowsLiveXmlConfig($this);
		$xml->setDirty();
	}
	public function getWindowsLiveSdkDirectory()
	{
		return SERIA_Base::getParam($this->getProviderId().'.sdkDirectory');
	}
	public function setApplicationId($appid)
	{
		SERIA_Base::setParam($this->getProviderId().'.applicationId', $appid);
		$xml = new WindowsLiveXmlConfig($this);
		$xml->setDirty();
	}
	public function getApplicationId()
	{
		return SERIA_Base::getParam($this->getProviderId().'.applicationId');
	}
	public function setSecret($secret)
	{
		SERIA_Base::setParam($this->getProviderId().'.secret', $secret);
		$xml = new WindowsLiveXmlConfig($this);
		$xml->setDirty();
	}
	public function getSecret()
	{
		return SERIA_Base::getParam($this->getProviderId().'.secret');
	}

	public function authenticated($wll, $consent, $user)
	{
		if ($user) {
			$userid = $user->getId();
			$attributes = array(
				'uid' => array($userid)
			);
			SERIA_Base::debug('Windows Live ID handler has logged in user with ID '.$attributes['uid'][0]);
			if (!isset($_SESSION['windows_live_returned_attrs']))
				$_SESSION['windows_live_returned_attrs'] = array();
			$retid = mt_rand().mt_rand();
			$_SESSION['windows_live_returned_attrs'][$retid] = $attributes;
			if (isset($_SESSION['windows_live_authentication_return']))
				$url = $_SESSION['windows_live_authentication_return'];
			else
				$url = false;
			if (!$url)
				$url = SERIA_HTTP_ROOT;
			$url = new SERIA_Url($url);
			$url->setParam('returned', $retid);
			SERIA_Base::redirectTo($url->__toString());
			die();
		}
	}
	/**
	 * 
	 * Called when the handler detects a failure. Will redirect to a failure site that alerts the user, and gives a few options for resolving.
	 * @param string $shortmsg A short message that identifies the full message. Must be unique for each message, and must not contain special characters.
	 * @param string $message A translated message telling the user what went wrong.
	 */
	public function failed($shortmsg, $message)
	{
		if (isset($_SESSION['windows_live_authentication_return']))
			$url = $_SESSION['windows_live_authentication_return'];
		else
			$url = false;
		if (!$url)
			$url = SERIA_HTTP_ROOT;
		$_SESSION[$shortmsg] = $message;
		$url = new SERIA_Url($url);
		$url->setParam('failed', $shortmsg);
		SERIA_Base::redirectTo($url->__toString());
		die();
	}
	public function authenticate($interactive=true, $reset=false, $guestLogin=false)
	{
		$state = new SERIA_AuthenticationState();
		if (isset($_GET['returned'])) {
			$returnId = $_GET['returned'];
			if (isset($_SESSION['windows_live_authentication_guest_login']))
				$guestLogin = $_SESSION['windows_live_authentication_guest_login'];
			else
				$guestLogin = true;
			if (isset($_SESSION['windows_live_returned_attrs'][$returnId]))
				$attributes = $_SESSION['windows_live_returned_attrs'][$returnId];
			else
				$attributes = false;
			if ($attributes)
				return $this->authenticatedExternally($this->getParameters(), $attributes, $guestLogin, $interactive);
		}
		if (isset($_GET['failed'])) {
			$shortmsg = $_GET['failed'];
			if (isset($_SESSION[$shortmsg]))
				$message = $_SESSION[$shortmsg];
			else
				$message = _t('An unknown error occured.');
			$continue = $state->getLast('continue');
			$comp = SERIA_Components::getComponent('windows_live_authprovider_component');
			$template = new SERIA_MetaTemplate();
			$template->addVariable('shortmsg', $shortmsg);
			$template->addVariable('message', $message);
			$retryUrl = SERIA_Url::current();
			$retryUrl->unsetParam('failed');
			$template->addVariable('retry', $retryUrl);
			$template->addVariable('retryString', $retryUrl->__toString());
			$template->addVariable('loginOptions', new SERIA_Url($continue)); /* Should discover that the user is not logged in and show options */
			$template->addVariable('loginOptionsString', $continue); /* Should discover that the user is not logged in and show options */
			echo $template->parse($comp->getTemplateFilename('failed'));
			return;
		}

		require(dirname(dirname(__FILE__)).'/inc/settings.php');
		require($this->getWindowsLiveSdkDirectory().'/lib/windowslivelogin.php');

		$wll = WindowsLiveLogin::initFromXml($KEYFILE);
		$wll->setDebug($DEBUG);

		if (!session_id())
			session_start();

		$_SESSION['windows_live_authentication_guest_login'] = $guestLogin;
		$_SESSION['windows_live_authentication_return'] = SERIA_Url::current()->__toString();
		$user = null;
		if (isset($_COOKIE[$WEBAUTHCOOKIE])) {
			$token = $_COOKIE[$WEBAUTHCOOKIE];
			if ($token)
				$user = $wll->processToken();
		}
		$token = null;
		if (isset($_COOKIE[$COOKIE])) {
			$cookie = $_COOKIE[$COOKIE];
			if ($cookie) {
				$token = $wll->processConsentToken($cookie);
			}
		}
		if ($token && !$token->isValid())
			$token = null;
		if ($token && $user) {
			/*
			 * $token is a WLL_ConsentToken
			 */
			$this->authenticated($wll, $token, $user);
			return;
		}
		/*
		 * Redirect to sign-in..
		 */
		if (isset($_GET['returned']))
			throw new SERIA_Exception('Login failed somehow');
		if (!$user) {
			$redirect = $wll->getLoginUrl();
			SERIA_Base::debug('Not logged in. Starting with logging in..');
		} else {
			if (false) {
				$redirect = $wll->getConsentUrl($OFFERS);
				SERIA_Base::debug('Already logged in. Requesting consent..');
			} else {
				SERIA_Base::debug('Not requesting consent..');
				$this->authenticated($wll, $token, $user);
				return;
			}
		}
		SERIA_Base::redirectTo($redirect);
		die();
	}
	public function authenticationHandler()
	{
		require(dirname(dirname(__FILE__)).'/inc/settings.php');
		require($this->getWindowsLiveSdkDirectory().'/lib/windowslivelogin.php');

		$wll = WindowsLiveLogin::initFromXml($KEYFILE);
		$wll->setDebug($DEBUG);

		$action = @$_REQUEST['action'];

		SERIA_Base::debug('Windows Live ID handling action: '.$action);
		switch ($action) {
			case 'delauth':
				SERIA_Base::debug('Consent reply..');
				$consent = $wll->processConsent($_REQUEST);

				// If a consent token is found, store it in the cookie that is
				// configured in the settings.php file and then redirect to
				// the main page.
				if ($consent) {
					/*
					 * $consent is a WLL_ConsentToken
					 */
					setcookie($COOKIE, $consent->getToken(), $COOKIETTL);
					$user = null;
					if (isset($_COOKIE[$WEBAUTHCOOKIE])) {
						$token = $_COOKIE[$WEBAUTHCOOKIE];
						if ($token)
							$user = $wll->processToken();
					}
					if ($user)
						$this->authenticated($wll, $consent, $user);
					else
						$this->failed('consentfailed', _t('Login failed.'));
					return;
				} else {
					setcookie($COOKIE);
				}
				break;
			case 'login':
				SERIA_Base::debug('Login reply..');
				$user = $wll->processLogin($_REQUEST);
				if ($user) {
					SERIA_Base::debug('Successful Microsoft Live login..');
					if ($user->usePersistentCookie())
						setcookie($WEBAUTHCOOKIE, $user->getToken(), $COOKIETTL);
					else
						setcookie($WEBAUTHCOOKIE, $user->getToken());
					$token = null;
					if (isset($_COOKIE[$COOKIE])) {
						$cookie = $_COOKIE[$COOKIE];
						if ($cookie) {
							$token = $wll->processConsentToken($cookie);
						}
					}
					if ($token && !$token->isValid())
						$token = null;
					if (!$token) {
						if (false) {
							SERIA_Base::debug('Requesting consent..');
							$redirect = $wll->getConsentUrl($OFFERS);
							SERIA_Base::redirectTo($redirect);
							die();
						} else
							SERIA_Base::debug('Not requesting consent..');
					}
					$this->authenticated($wll, $token, $user);
					return;
				} else {
					setcookie($WEBAUTHCOOKIE);
					$this->failed('loginfailed', _t('Login failed.'));
				}
				break;
			default:
				/*
				 * OOps..
				 */
				$this->failed('Unknown_action_'.$action, _t('Unknown action %ACTION%.', array('ACTION' => $action)));
		}
	}

	public function logout()
	{
	}

	public function configureProvider($provider, $params=array())
	{
		if (!isset($params['redirect']))
			$params['redirect'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['cancel']))
			$params['cancel'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['submitCaption']))
			$params['submitCaption'] = _t('Save');
		if (sizeof($_POST)) {
			$provider->setWindowsLiveSdkDirectory($_POST['sdkDir']);
			$provider->setApplicationId($_POST['appId']);
			$provider->setSecret($_POST['secret']);
			SERIA_Base::redirectTo($params['redirect']);
			die();
		}
		$sdkdir = $provider->getWindowsLiveSdkDirectory();
		$appid = $provider->getApplicationId();
		$secret = $provider->getSecret();
		?>
		<form method='post'>
			<div>
				<h2><label for='sdkDirectory'><?php echo _t('Windows Live PHP-SDK directory');?></label></h2>
				<input type='text' id='sdkDirectory' name='sdkDir' value="<?php echo htmlspecialchars($sdkdir); ?>" %XHTML_CLOSE_TAG%>
			</div>
			<div>
				<h2><label for='applicationId'><?php echo _t('Application ID')?></label></h2>
				<input type='text' id='applicationId' name='appId' value="<?php echo htmlspecialchars($appid); ?>" %XHTML_CLOSE_TAG%>
			</div>
			<div>
				<h2><label for='applicationSecret'><?php echo _t('Secret')?></label></h2>
				<input type='text' id='applicationSecret' name='secret' value="<?php echo htmlspecialchars($secret); ?>" %XHTML_CLOSE_TAG%>
			</div>
			<div>
				<button type='submit'><?php echo $params['submitCaption']; ?></button>
				<button type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($params['cancel'])); ?>;"><?php echo _t('Cancel'); ?></button>
			</div>
		</form>
		<?php
	}
}