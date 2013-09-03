<?php

class FacebookAuthprovider extends SERIA_GenericAuthprovider
{
	public static function loadProviders() /* Ok */
	{
		$retv =& parent::loadProviders();
		SERIA_Authproviders::addProvider(new FacebookAuthprovider());
		return array_merge($retv, array(
			'configure' => array(
				'caption' => _t('Configure Facebook SSO'),
				'call' => array('FacebookAuthprovider', 'configureProvider'),
				'version' => 2
			)
		));
	}

	public function getParameters() /* Ok */
	{
		return array(
			'authsources' => array(
				'facebook' => array(
					'authfacebook:Facebook',
					'app_id' => SERIA_Base::getParam('SimpleSAML_Facebook_app_id'),
					'secret' => SERIA_Base::getParam('SimpleSAML_Facebook_secret'),
					'ext_params' => array('email')
				),
			),
			'config' => array(
			),
			'authsource' => 'facebook',
			'attributes' => array(
				'defaults' => array(
					'email' => '',
					'firstName' => '',
					'lastName' => ''
				),
				'load' => array(
					'unique' => 'id',
					'firstName' => 'first_name',
					'lastName' => 'last_name',
					'displayName' => 'name',
					'email' => 'email'
				),
				'combinations' => array(
				),
				'fillIn' => array(
					/*
					 * Fill in these fields into the SERIA_User object
					 */
					'unique',
					'email',
					'firstName',
					'lastName',
					'displayName'
				)
			),
			'safeEmail' => true
		);
	}

	public function isAvailable() /* Ok */
	{
		return (SERIA_Base::getParam('SimpleSAML_Facebook_app_id') && SERIA_Base::getParam('SimpleSAML_Facebook_secret'));
	}

	/**/
	public function getProviderId() /* Ok */
	{
		return 'facebook_provider';
	}
	public function getName() /* Ok */
	{
		return _t('Facebook authentication');
	}

	public function configureProvider($provider, $params=array()) /* Ok */
	{
		if (!isset($params['redirect']))
			$params['redirect'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['cancel']))
			$params['cancel'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['submitCaption']))
			$params['submitCaption'] = _t('Save');
		if (isset($_POST['app_id']) && isset($_POST['secret'])) {
			SERIA_Base::setParam('SimpleSAML_Facebook_app_id', $_POST['app_id']);
			SERIA_Base::setParam('SimpleSAML_Facebook_secret', $_POST['secret']);
			SERIA_Base::setParam('FacebookAuthprovider_disableLogoutFacebookOnLogout', (isset($_POST['logoutFacebook']) && $_POST['logoutFacebook'] ? false : true));
			SERIA_Base::redirectTo($params['redirect']);
			die();
		}
		$secret = SERIA_Base::getParam('SimpleSAML_Facebook_secret');
		$app_id = SERIA_Base::getParam('SimpleSAML_Facebook_app_id');
		$logoutFacebook = SERIA_Base::getParam('FacebookAuthprovider_disableLogoutFacebookOnLogout');
		$logoutFacebook = $logoutFacebook ? false : true;
		?>
		<form method='post'>
			<input type='hidden' name='id' value="<?php echo htmlspecialchars($provider->getProviderId()); ?>" %XHTML_CLOSE_TAG%>
			<div>
				<h1 class='legend'><?php echo htmlspecialchars(_t('Configure Facebook SSO')); ?></h1>
				<p><?php echo htmlspecialchars(_t('Insert your API-id and secret below.')); ?></p>
			</div>
			<div>
				<table cellspacing='0' border='0'>
					<tr>
						<td><label for='app_id_elem'><?php echo htmlspecialchars('APP-id: '); ?></label></td>
						<td><input id='app_id_elem' type='text' name='app_id' value="<?php echo htmlspecialchars($app_id); ?>" style='width: 320px;' %XHTML_CLOSE_TAG%></td>
					</tr>
					<tr>
						<td><label for='secret_element'><?php echo htmlspecialchars('Secret key: '); ?></label></td>
						<td><input id='secret_element' type='text' name='secret' value="<?php echo htmlspecialchars($secret); ?>" style='width: 320px;' %XHTML_CLOSE_TAG%></td>
					</tr>
					<tr>
						<td colspan='2'>
							<label>
								<input type='checkbox' name='logoutFacebook' value='1'<?php echo ($logoutFacebook ? ' checked="checked"' : ''); ?> %XHTML_CLOSE_TAG%> <?php  echo htmlspecialchars('Logout from facebook at user logout.'); ?>
							</label>
						</td>
					</tr>
				</table>
			</div>
			<div>
				<button type='submit'><?php echo $params['submitCaption']; ?></button>
				<button type='button' onclick="<?php echo htmlspecialchars('top.location.href = '.SERIA_Lib::toJSON($params['cancel']).';'); ?>"><?php echo htmlspecialchars(_t('Cancel')); ?></button>
			</div>
		</form>
		<?php
	}

	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM) /* Ok */
	{
		/*
		 * XXX - Using forceFetch because SERIA_Base::getParam caching is severely broken.
		 */
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				return (SERIA_Base::getParam('simplesaml_provider.'.$this->getProviderId().'.system_enabled', true) ? true : false);
			case SERIA_IAuthprovider::LOGIN_GUEST:
				return (SERIA_Base::getParam('simplesaml_provider.'.$this->getProviderId().'.guest_enabled', true) ? true : false);
			default:
				return false;
		}
	}
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM) /* Ok */
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				SERIA_Base::setParam('simplesaml_provider.'.$this->getProviderId().'.system_enabled', ($enabled ? 1 : 0));
				break;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				SERIA_Base::setParam('simplesaml_provider.'.$this->getProviderId().'.guest_enabled', ($enabled ? 1 : 0));
				break;
		}
	}

	protected function callLoginManager($class, $providerId, $params, $attributes, $guestLogin, $interactive) /* OK */
	{
		$mgr = new FacebookLoginManager();
		$mgr->login($class, $providerId, $params, $attributes, $guestLogin, $interactive);
	}

	public function getFacebook()
	{
		$parameters = $this->getParameters();
		$facebook = new Facebook(array(
			'appId' => $parameters['authsources']['facebook']['app_id'],
			'secret' => $parameters['authsources']['facebook']['secret']
		));
		return $facebook;
	}

	public function authenticate($interactive=true, $reset=false, $guestLogin=false) /* TODO - Convert to new */
	{
		$facebook = $this->getFacebook();
		if (isset($_GET['fbauth_invoked']) && $_GET['fbauth_invoked']) {
			/* Back! */
			SERIA_Base::debug('Returned from Facebook auth!');
			$fb_user = $facebook->getUser();
			if ($fb_user) {
				$user_profile = $facebook->api('/me');
				return $this->authenticatedExternally($this->getParameters(), $user_profile, $guestLogin, $interactive);
			} else {
				if (SERIA_AuthenticationState::available()) {
					$state = new SERIA_AuthenticationState();
					$state->terminate('abort');
				} else {
					SERIA_Base::redirectTo(SERIA_HTTP_ROOT);
				}
			}
		}
		$fbreturn = SERIA_Url::current();
		$fbreturn->setParam('fbauth_invoked', 'done');
		$loginUrl = $facebook->getLoginUrl(array(
			'redirect_uri' => $fbreturn->__toString(),
			'scope' => array('email')
		));
		SERIA_Base::redirectTo($loginUrl);
	}
	public function beforeLogout()
	{
	}
	public function logout()
	{
		$facebook = $this->getFacebook();
		if ($facebook->getUser() && !SERIA_Base::getParam('FacebookAuthprovider_disableLogoutFacebookOnLogout')) {
			$logoutUrl = $facebook->getLogoutUrl(array(
				'next' => SERIA_Url::current()->__toString()
			));
			/* Redirect after logout complete */
			return new SERIA_Url($logoutUrl);
		}
	}
}