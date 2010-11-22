<?php

class SERIA_ExternalAuthprovider extends SERIA_ExternalAuthproviderDB implements SERIA_IAuthprovider
{
	protected static $externalProviders;
	protected $propertyLists = array();
	protected $rpc = null;

	public static function loadProviders()
	{
		$providers = new SERIA_FluentQuery('SERIA_ExternalAuthprovider');
		self::$externalProviders = array();
		foreach ($providers as $provider) {
			SERIA_Authproviders::addProvider($provider);
			self::$externalProviders[] =& $provider;
			unset($provider);
		}
		/*
		 * Returns available operations
		 */
		return array(
			'new' => array(
				'caption' => _t('Create new external Seria Platform authentication'),
				'call' => array('SERIA_ExternalAuthprovider', 'newProvider')
			),
			'configure' => array(
				'caption' => _t('Configure external Seria Platform authentication'),
				'call' => array('SERIA_ExternalAuthprovider', 'configureProvider'),
				'version' => 2
			),
			'delete' => array(
				'caption' => _t('Delete external Seria Platform authentication'),
				'call' => array('SERIA_ExternalAuthprovider', 'deleteProvider')
			)
		);
	}

	public static function &getProviderByHostname($hostname)
	{
		foreach (self::$externalProviders as &$provider) {
			if ($provider->getHostname() == $hostname)
				return $provider;
		}
	}

	public function getHostname()
	{
		if ($this->isAvailable())
			return $this->rpc->getHostname();
		else
			return false;
	}

	/**/
	public function getProviderId()
	{
		return 'external_'.sha1($this->get('remote'));
	}
	public function getName()
	{
		return _t('Seria Platform site: %SITE%', array('site' => $this->get('remote')));
	}
	public function isEnabled($loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				$var = 'system_enabled';
				break;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				$var = 'guest_enabled';
				break;
			case SERIA_IAuthprovider::LOGIN_AUTO:
				$var = 'auto_enabled';
				break;
			default:
				return false;
		}
		return ($this->get($var) ? true : false);
	}
	public function setEnabled($enabled, $loginType=SERIA_IAuthprovider::LOGIN_SYSTEM)
	{
		switch ($loginType) {
			case SERIA_IAuthprovider::LOGIN_SYSTEM:
				$var = 'system_enabled';
				break;
			case SERIA_IAuthprovider::LOGIN_GUEST:
				$var = 'guest_enabled';
				break;
			case SERIA_IAuthprovider::LOGIN_AUTO:
				$var = 'auto_enabled';
				break;
			default:
				return;
		}
		if ($enabled)
			$this->set($var, 1);
		else
			$this->set($var, 0);
		$this->save();
	}
	public function isAvailable()
	{
		if ($this->rpc !== null) {
			if ($this->rpc !== false)
				return true;
			else
				return false;
		}
		$row = SERIA_Base::db()->query('SELECT * FROM {rpc_remote_services} WHERE service = :remote AND service = hostname', array('remote' => $this->get('remote')))->fetchAll(PDO::FETCH_ASSOC);
		if ($row)
			$row = $row[0];
		if ($row && $row['client_id'] && $row['client_key']) {
			$this->rpc = SERIA_RPCClient::connect($this->get('remote'), 'SERIA_ExternalAuthenticationAgent');
			return true;
		} else {
			$this->rpc = false;
			return false;
		}
	}
	public function updateUserMeta($user=false)
	{
		if ($user === false) {
			$user = SERIA_Base::user();
			if ($user === false)
				return;
		}
		$remoteId = $this->getUserRemoteId($user);
		if ($remoteId === false)
			return;
		$sync = $this->rpc->updateUserMeta($remoteId, $meta = $user->getAllMetaExtended());
		$m = array();
		foreach ($meta as $data) {
			$m[$data['name']] = array(
				'value' => $data['value'],
				'timestamp' => $data['timestamp']
			);
		}
		unset($meta);
		$meta =& $m;
		unset($m);
		foreach ($sync as $check) {
			if (isset($meta[$check['name']])) {
				$mydata = $meta[$check['name']];
				if ($mydata['timestamp'] < $check['timestamp'])
					$user->_setMetaExtended($check['name'], $check['value'], $check['timestamp']);
			} else
				$user->_setMetaExtended($check['name'], $check['value'], $check['timestamp']);
		}
		unset($meta);
		unset($sync);
		if (session_id())
			$_SESSION['userMetaUpdateTime'.$user->get('id')] = time();
	}
	public function periodicUpdateUserMeta($user=false, $lag=300)
	{
		if ($user === false) {
			$user = SERIA_Base::user();
			if ($user === false)
				return;
		}
		$time = time();
		if (!isset($_SESSION['userMetaUpdateTime'.$user->get('id')]) || $_SESSION['userMetaUpdateTime'.$user->get('id')] > ($time+$lag) || $_SESSION['userMetaUpdateTime'.$user->get('id')] < ($time-$lag))
			$this->updateUserMeta($user);
	}
	public function getUserObject($remoteUid)
	{
		$loginUser = SERIA_PropertyList::query('SERIA_User', 'externalUser:'.$this->rpc->getHostname(), $remoteUid);
		if (count($loginUser) > 1)
			throw new SERIA_Exception('Some user has managed to get duplicate accounts based on external login.');
		if (count($loginUser) == 1)
			return $loginUser[0];
		return null;
	}
	public function getUserRemoteId($user)
	{
		if (!$this->isAvailable())
			throw new SERIA_Exception('This provider is not configured/available: Cannot retrieve the remote user id!');
		$plist = SERIA_PropertyList::createObject($user);
		$remid = $plist->get('externalUser:'.$this->rpc->getHostname());
		if ($remid !== NULL)
			return $remid;
		else
			return false;
	}
	public function authenticate($interactive=true, $reset=false, $guestLogin=false)
	{
		if (!$this->isAvailable())
			throw new SERIA_Exception('External auth is not available for host (RPC configuration required): '.$this->get('remote'));
		if(!session_id())
			session_start();
		$status = 0;
		if (isset($_SESSION['remoteAuthenticationTokenService']) &&
		    $_SESSION['remoteAuthenticationTokenService'] == $this->getProviderId() &&
		    isset($_SESSION['remoteAuthenticationToken'])) {
			/*
			 * Check whether this is a returned session from the remote login.
			 */
			$token = $_SESSION['remoteAuthenticationToken'];
			unset($_SESSION['remoteAuthenticationToken']); /* One-time use */
			if (!$reset && isset($_GET['failure']))
				return true;
			if (!$reset && isset($_GET['code']))
				$status = 1;
		}
		SERIA_Base::debug('(SERIA_ExternalAuthprovider:object)->authenticate(...): Reset: '.($reset ? 'On' : 'Off'));
		SERIA_Base::debug('(SERIA_ExternalAuthprovider:object)->authenticate(...): Status: '.$status);
		SERIA_Base::debug('(SERIA_ExternalAuthprovider:object)->authenticate(...): Token: '.$token);
		switch ($status) {
			case 0:
				/* Stage I - Get an authentication token from the remote */
				$token = $this->rpc->getToken();
				$_SESSION['remoteAuthenticationTokenService'] = $this->getProviderId();
				$_SESSION['remoteAuthenticationToken'] = $token;
				/* redirect to login */
				SERIA_Base::redirectTo(SERIA_ExternalAuthenticationAgent::getUrl($this, $this->rpc, $token, null, $interactive, $guestLogin));
				die();
				break;
			case 1:
				/* Stage II - Check whether the login was successful */
				$status = $this->rpc->getStatus($token, $_GET['code']);
				if ($status[0] == SERIA_ExternalAuthenticationAgent::STATUS_OK ||
				    $status[0] == SERIA_ExternalAuthenticationAgent::STATUS_GUEST) {
				    if ($status[0] == SERIA_ExternalAuthenticationAgent::STATUS_GUEST)
				    	SERIA_Base::blockSystemAccess();
					/*
					 * Get the remote uid, and fetch user-data.
					 */
					$user_data = $this->rpc->getUserData($token);
					/* Find the local user */
					$user = $this->getUserObject($user_data['uid']);
					$safeEmails = $this->rpc->getSafeEmailAddresses($user_data['uid']);
					if ($user === null) {
						/*
						 * Search for a match for safe email addresses, local <--> remote
						 */
						foreach ($safeEmails as $email) {
							if (($user = SERIA_SafeEmailUsers::getUserByEmail($email)) !== null)
								break;
						}
						if ($user !== null) {
							/*
							 * Mark this user for later..
							 */
							$plist = SERIA_PropertyList::createObject($user);
							$plist->set('externalUser:'.$this->rpc->getHostname(), $user_data['uid']);
							$plist->save();
						}
					}
					if ($user !== null) {
						/*
						 * Ok.. We trust the remote server. Let's go ahead and set login successful..
						 */
						foreach ($safeEmails as $email)
							SERIA_SafeEmailUsers::registerUserEmail($user, $email);
						SERIA_Base::user($user);
					} else {
						/*
						 * This is a new external login. For now we will just create a new user account,
						 * in the future it should be possible to connect user accounts based on a secondary
						 * login.
						 */
						$user = new SERIA_User();
						$init = array(
							'is_administrator' => 0,
							'enabled' => 1,
							'password_change_required' => 0,
							'password' => 'local_blocked_random_'.sha1(mt_rand().mt_rand().mt_rand().mt_rand().mt_rand())
						);
						foreach ($init as $name => $value)
							$user->set($name, $value);
						$xferFields = array(
							'firstName',
							'lastName',
							'displayName',
							'email'
						);
						foreach ($xferFields as $xfer)
							$user->set($xfer, $user_data[$xfer]);
						if ($user_data['guestAccount'])
							$access_level = 0;
						else if ($user_data['is_administrator'])
							$access_level = 2;
						else
							$access_level = 1;
						$max_level = $this->get('accessLevel');
						if ($access_level > $max_level)
							$access_level = $max_level;
						$user->set('guestAccount', ($access_level == 0 ? 1 : 0));
						$user->set('is_administrator', ($access_level == 2 ? 1 : 0));
						$user->set('username', $user_data['uid'].'@'.$this->rpc->getHostname());
						try {
							SERIA_Base::elevateUser(array($user, 'validate'));
						} catch (SERIA_ValidationException $e) {
							$errors = $e->getValidationErrors();
							if (isset($errors['displayName'])) {
								/* Display name conflict: try to resolve */
								$genDisp = $user_data['displayName'].' at '.$this->rpc->getHostname();
								$user->set('displayName', $genDisp);
								try {
									SERIA_Base::elevateUser(array($user, 'validate'));
								} catch (SERIA_ValidationException $e) {
									$errors = $e->getValidationErrors();
									if (isset($errors['displayName'])) {
										/* Second conflict, try a few more times to resolve this. */
										$num = 0;
										while (true) {
											$user->set('displayName', $genDisp.' '.$num);
											$valid = false;
											try {
												SERIA_Base::elevateUser(array($user, 'validate'));
												$valid = true;
											} catch (SERIA_ValidationException $e) {
												$errors = $e->getValidationErrors();
												if (!isset($errors['displayName']))
													throw $e;
												if ($num >= 10)
													throw $e; /* No more tries */
											}
											if ($valid)
												break;
											$num++;
										}
									}
								}
							} else
								throw $e;
						}
						if (SERIA_Base::elevateUser(array($user, 'save'))) {
							/* Get a clean object, just to be sure. */
							$uid = $user->get('id');
							if (!$uid || !is_numeric($uid))
								throw new SERIA_Exception('Unexpected invalid user-id');
							$user = SERIA_User::createObject($uid);
							if (!$user)
								throw new SERIA_Exception('User-object nonex. or otherwise evaluating to false');
							$plist = SERIA_PropertyList::createObject($user);
							$plist->set('externalUser:'.$this->rpc->getHostname(), $user_data['uid']);
							$plist->save();
							foreach ($safeEmails as $email)
								SERIA_SafeEmailUsers::registerUserEmail($user, $email);
						} else
							throw new SERIA_Exception('Failed to save user');
						/* Finished. Let them go ahead.. */
						SERIA_Base::user($user);
					}
					/*
					 * Sync metadata with server
					 */
					$this->periodicUpdateUserMeta($user, 20);
					$_SESSION['AUTHPROVIDERS_REMOTE_XML'] = $status[1];
					$_SESSION['AUTHPROVIDERS_REMOTE_SID'] = $status[2];
					$_SESSION['authproviders_external_discovery_latest'] = $status[3];
					SERIA_PersistentExternalAuthentication::authenticatedExternally();
				} else if ($status[0] == SERIA_ExternalAuthenticationAgent::STATUS_FAILED) {
					/*
					 * Unset the discovery cookie.
					 */
					$cookieName = 'logindiscovery'.sha1($this->rpc->getHostname());
					$_COOKIE[$cookieName] = 'failure';
				} else {
					SERIA_Base::debug('ERROR: Authentication status code is not known: '.$status[0].' (Update me!)');
					SERIA_Base::debug('Can\'t authenticate due to protocol error!');
				}
				if (isset($_GET['return']) && $_GET['return'])
					return $_GET['return'];
				return true; /* We have successfully gone through the procedure, SERIA_Base tells the login status */
		}
	}
	public static function automaticDiscoveryPreCheck()
	{
		$prefix = 'logindiscovery';
		$prelen = strlen($prefix);
		foreach ($_COOKIE as $name => $value) {
			if (strlen($name) > $prelen && substr($name, 0, $prelen) == $prefix) {
				if (substr($value, 0, 5) == 'auto:')
					return true;
			}
		}
		return false;
	}

	protected function autoCalledWithLogin()
	{
		SERIA_PersistentExternalAuthentication::externalAuthenticationRefresh($this);
	}
	public function automaticDiscovery()
	{
		if ($this->isAvailable()) {
			$cookieName = 'logindiscovery'.sha1($this->rpc->getHostname());
			if (isset($_COOKIE[$cookieName])) {
				if (SERIA_Base::user() !== false) {
					SERIA_Base::debug('Automatic logout discovery with cookie..');
					if ($_COOKIE[$cookieName] == $_SESSION['authproviders_external_discovery_latest']) {
						$this->autoCalledWithLogin();
						return false;
					} else {
						SERIA_Base::debug('Logging out because of logout cookie, or updated login cookie.');
						/* Logged out externally: detach provider and log out local */
						$component = SERIA_Components::getComponent('seria_authproviders');
						$component->loggedInByProvider(null);
						SERIA_Base::user(NULL); /* log out */
						return false;
					}
				} else if (substr($_COOKIE[$cookieName], 0, 5) == 'auto:') {
					/*
					 * Login
					 */
					return true; /* Whohoo! */
				}
			} else if (SERIA_Base::user() !== false) {
				SERIA_Base::debug('Automatic logout discovery without cookie..');
				if (isset($_SESSION['authproviders_external_discovery_latest'])) {
					SERIA_Base::debug('Logging out because login cookie has been lost.');
					/* Logged out externally: detach provider and log out local */
					$component = SERIA_Components::getComponent('seria_authproviders');
					$component->loggedInByProvider(null);
					SERIA_Base::user(NULL);
					unset($_SESSION['authproviders_external_discovery_latest']);
					return false;
				}
				$this->autoCalledWithLogin();
			}
		}
		return false;
	}
	protected function logoutAction($continue)
	{
		return new SERIA_ActionUrl('logoutProvider'.sha1($this->getProviderId()), $_SESSION['authproviders_external_discovery_latest'], $continue);
	}
	public function logout()
	{
		if (!$this->isAvailable())
			throw new SERIA_Exception('External auth is not available for host (RPC configuration required): '.$this->get('remote'));
		if (SERIA_Base::user() !== false)
			throw new SERIA_Exception('Looping logout will result!');
	}
	public function beforeLogout()
	{
		if (!$this->isAvailable())
			throw new SERIA_Exception('External auth is not available for host (RPC configuration required): '.$this->get('remote'));
		$logoutProviderAction = $this->logoutAction(SERIA_Url::current()->__toString());
		if ($logoutProviderAction->invoked()) {
			/* Logged out externally: detach provider and log out local */
			$component = SERIA_Components::getComponent('seria_authproviders');
			$component->loggedInByProvider(null);
			SERIA_Base::redirectTo($logoutProviderAction->getState());
			die();
		}
		$cookieName = 'logindiscovery'.sha1($this->rpc->getHostname());
		SERIA_Base::debug('Handling a logout with expected discovery-cookie name: '.$cookieName);
		if (isset($_SESSION['authproviders_external_discovery_latest']))
			SERIA_Base::debug('Expecting authentication serial (autodiscover logout): '.$_SESSION['authproviders_external_discovery_latest']);
		if (isset($_COOKIE[$cookieName]))
			SERIA_Base::debug('Found authentication serial (cookie): '.$_COOKIE[$cookieName]);
		if (isset($_SESSION['authproviders_external_discovery_latest']) && $_COOKIE[$cookieName] &&
			$_SESSION['authproviders_external_discovery_latest'] && $_COOKIE[$cookieName] &&
			$_SESSION['authproviders_external_discovery_latest'] != $_COOKIE[$cookieName]) {
			/*
			 * Autodiscovery cookie does not match logged in user.. don't redirect for logout, just
			 * unset login here.
			 */
			SERIA_Base::debug('Picked up a login change from cookie, just removing my login (logout handler)');
			return;
		}
		$url = SERIA_ExternalAuthenticationAgent::getLogoutUrl($this->rpc, $logoutProviderAction->__toString());
		SERIA_Base::redirectTo($url);
		die();
	}

	public function userObjectHook($user)
	{
		SERIA_Base::debug('External authprovider has been notified of a user object: '.$user->get('username'));
		$this->periodicUpdateUserMeta($user);
	}
	public function userObjectPropertyListCreate($user)
	{
		$id = $user->get('id');
		if ($this->propertyList[$id] === false)
			$this->propertyList[$id] = SERIA_RemotePropertyList::createObject($user);
		return $this->propertyList[$id];
	}

	public static function newProvider()
	{
		$obj = new SERIA_ExternalAuthprovider();
		$form = new SERIA_ExternalAuthproviderForm($obj);
		if (sizeof($_POST) && $form->receive($_POST)) {
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php');
			die();
		}
		?>
			<h1><?php echo htmlspecialchars($form->caption()); ?></h1>
		<?php
		echo $form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/displayTableForm.php');
	}
	public static function configureProvider($provider, $params=array())
	{
		if (!isset($params['redirect']))
			$params['redirect'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['cancel']))
			$params['cancel'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['submitCaption']))
			$params['submitCaption'] = _t('Save');
		$form = new SERIA_ExternalAuthproviderForm($provider);
		if (sizeof($_POST) && $form->receive($_POST)) {
			SERIA_Base::redirectTo($params['redirect']);
			die();
		}
		?>
			<h1><?php echo htmlspecialchars($form->caption()); ?></h1>
		<?php
		echo $form->begin();
		echo $form->hidden('guest_enabled');
		echo $form->hidden('system_enabled');
		echo $form->hidden('auto_enabled');
		?>
		<div>
			<table border='0'>
				<tr>
					<th align='left' style='vertical-align: top;'><?php echo $form->label('remote'); ?>: </th>
					<td>
						<div><?php echo $form->text('remote', array('style' => 'width: 320px;')); ?></div>
						<?php
							if (($error = $form->error('remote')))
								echo '<p class=\'error\'>'.$error.'</p>';
						?>
					</td>
				</tr>
				<tr>
					<th align='left' style='vertical-align: top;'><?php echo $form->label('rpc_client_id'); ?>: </th>
					<td>
						<div><?php echo $form->text('rpc_client_id', array('style' => 'width: 320px;')); ?></div>
						<?php
							if (($error = $form->error('rpc_client_id')))
								echo '<p class=\'error\'>'.$error.'</p>';
						?>
					</td>
				</tr>
				<tr>
					<th align='left' style='vertical-align: top;'><?php echo $form->label('rpc_key'); ?>: </th>
					<td>
						<div><?php echo $form->text('rpc_key', array('style' => 'width: 320px;')); ?></div>
						<?php
							if (($error = $form->error('rpc_key')))
								echo '<p class=\'error\'>'.$error.'</p>';
						?>
					</td>
				</tr>
				<tr>
					<th align='left' style='vertical-align: top;'><?php echo $form->label('accessLevel'); ?>: </th>
					<td><?php
						$sel = array('', '', '');
						$sel[$provider->get('accessLevel')] = ' selected=\'selected\'';
					?>
						<div>
							<select name='accessLevel' id='accessLevel' style='width: 320px;'>
								<option value='0'<?php echo $sel[0]; ?>><?php echo htmlspecialchars(_t('Guest')); ?></option>
								<option value='1'<?php echo $sel[1]; ?>><?php echo htmlspecialchars(_t('System')); ?></option>
								<option value='2'<?php echo $sel[2]; ?>><?php echo htmlspecialchars(_t('Administrator')); ?></option>
							</select>
						</div>
						<?php
							if (($error = $form->error('accessLevel')))
								echo '<p class=\'error\'>'.$error.'</p>';
						?>
					</td>
				</tr>
			</table>
			<div>
				<?php echo $form->submit('submit', $params['submitCaption']); ?>
				<input type='button' onclick="top.location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($params['cancel'])); ?>;" value="<?php echo htmlspecialchars(_t('Cancel')); ?>">
			</div>
		</div>
		<?php
		echo $form->end();
	}
	public static function deleteProvider($provider)
	{
		if (isset($_POST['id'])) {
			$provider->delete();
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php');
			die();
		}
		?>
		<form method='post'>
			<input type='hidden' name='id' value="<?php echo htmlspecialchars($provider->getProviderId()); ?>" %XHTML_CLOSE_TAG%>
			<div>
				<h1><?php echo htmlspecialchars(_t('Delete external authentication: %NAME%', array('NAME' => $provider->get('remote')))); ?></h1>
				<p><?php echo htmlspecialchars(_t('Are you sure you want to delete external authentication from %NAME%?', array('NAME' => $provider->get('remote')))); ?></p>
			</div>
			<div>
				<button type='submit'><?php echo htmlspecialchars(_t('Delete')); ?></button>
				<button type='button' onclick="<?php echo htmlspecialchars('top.location.href = '.SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php').';'); ?>"><?php echo htmlspecialchars(_t('Cancel')); ?></button>
			</div>
		</form>
		<?php
	}
}
