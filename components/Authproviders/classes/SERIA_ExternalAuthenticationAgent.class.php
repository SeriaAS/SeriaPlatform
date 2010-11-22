<?php

class SERIA_ExternalAuthenticationAgent implements SERIA_RPCServer
{
	const STATUS_STARTED = 0;
	const STATUS_OK = 1;
	const STATUS_FAILED = 2;
	const STATUS_GUEST = 3;

	public static function rpc_getToken()
	{
		SERIA_RPCHost::requireAuthentication();
		$locale = SERIA_Locale::getLocale();
		$token = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
		$code = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
		$insert = array(
			'token' => $token,
			'status' => 0,
			'timeout' => $locale->timeToSQL(time() + 3600) /* 1 hour timeout */,
			'code' => $code
		);
		SERIA_Base::db()->insert('{authprovider_token_tracking}', array_keys($insert), $insert);
		return $token;
	}
	public static function getUrl(&$provider, $remoteAgent, $token, $backtome=null, $interactive=true, $guestLogin=false)
	{
		if ($backtome === null)
			$backtome = SERIA_Authproviders::getHandshakeReturnUrl($provider, $interactive, $guestLogin);
		$state = new SERIA_AuthenticationState();
		$state->assert();
		$state->set('interactive', $interactive);
		$state->set('guestLogin', $guestLogin);
		$backtome = $state->stampUrl($backtome);
		$url = $remoteAgent->getBaseUrl().'/seria/components/Authproviders/pages/externalRequest.php?token='.urlencode($token);
		if ($backtome !== false)
			$url .= '&from='.urlencode($backtome);
		if (!$interactive)
			$url .= '&interactive=no';
		if ($guestLogin)
			$url .= '&guest=yes';
		return $url;
	}
	public static function getLogoutUrl($remoteAgent, $backtome=null)
	{
		if ($backtome === null) {
			$backtome = SERIA_HTTP_ROOT;
			$len = strlen($backtome);
			if ($len > 0 && $backtome[$len - 1] == '/')
				$backtome = substr($backtome, 0, $len - 1);
			$backtome .= $_SERVER['REQUEST_URI'];
		}
		$url = $remoteAgent->getBaseUrl().'/seria/components/Authproviders/pages/externalLogout.php';
		$params = array();
		if ($backtome !== false)
			$params[] = 'from='.urlencode($backtome);
		if (count($params))
			$url .= '?'.implode('&', $params);
		return $url;
	}
	public static function setStatus($token, $status)
	{
		if ($status == self::STATUS_OK || $status == self::STATUS_GUEST) {
			$component = SERIA_Components::getComponent('seria_authproviders');
			if (!$component)
				throw new SERIA_Exception('Authproviders component not found!');
			$sid = session_id();
			if (!$sid)
				throw new SERIA_Exception('No session has been started, and still we would claim to be logged in..');
			$_SESSION['AUTHPROVIDERS_SID'] = array(time(), session_id(), SERIA_Base::user()->get('id'), $sid);
			new SERIA_UserLoginXml($sid, SERIA_Base::user());
			$php_sid = session_id();
 			if (!SERIA_Base::db()->exec('UPDATE {authprovider_token_tracking} SET status = :status, sid = :sid, php_sid = :php_sid, autodiscovery_id = :autodiscovery_id WHERE token = :token', array('token' => $token, 'status' => $status, 'sid' => $sid, 'php_sid' => $php_sid, 'autodiscovery_id' => $component->getLoginDiscoveryCookie())))
				throw new SERIA_Exception('Failed to update status (Somebody tampering?).');
		} else
			if (!SERIA_Base::db()->exec('UPDATE {authprovider_token_tracking} SET status = :status WHERE token = :token', array('token' => $token, 'status' => $status)))
				throw new SERIA_Exception('Failed to update status (Somebody tampering?).');
	}
	public static function rpc_getStatus($token, $code)
	{
		SERIA_RPCHost::requireAuthentication();
		$row = SERIA_Base::db()->query('SELECT status, sid, php_sid, autodiscovery_id FROM {authprovider_token_tracking} WHERE token = :token AND code = :code', array(
			'token' => $token,
			'code' => $code
		))->fetchAll(PDO::FETCH_ASSOC);
		if ($row) {
			$row = $row[0];
			if ($row['status']) {
				return array($row['status'], SERIA_UserLoginXml::getUserXmlUrl($row['sid']), $row['php_sid'], $row['autodiscovery_id']);
			}
		}
		return array(self::STATUS_FAILED);
	}
	public static function setUid($token, $uid)
	{
		$rowc = SERIA_Base::db()->exec('UPDATE {authprovider_token_tracking} SET uid = :uid WHERE token = :token', array('token' => $token, 'uid' => $uid));
		if (!$rowc)
			throw new SERIA_Exception('Failed to update uid (Somebody tampering?). '.($rowc !== false ? $rowc : 'false').' '.$token);
	}
	public static function rpc_getUserData($token)
	{
		SERIA_RPCHost::requireAuthentication();
		$row = SERIA_Base::db()->query('SELECT uid FROM {authprovider_token_tracking} WHERE token = :token', array(
			'token' => $token,
		))->fetchAll(PDO::FETCH_ASSOC);
		if ($row) {
			$row = $row[0];
			if ($row['uid']) {
				$user = SERIA_Fluent::createObject('SERIA_User', $row['uid']);
				$fields = array(
					"firstName",
					"lastName",
					"displayName",
					"username",
					"email",
					'is_administrator',
					'guestAccount'
				);
				$values = array('uid' => $row['uid']);
				foreach ($fields as $field)
					$values[$field] = $user->get($field);
				return $values;
			}
		}
		throw new SERIA_Exception('Bad token');
	}
	public static function getCode($token)
	{
		$row = SERIA_Base::db()->query('SELECT code FROM {authprovider_token_tracking} WHERE token = :token', array(
			'token' => $token,
		))->fetchAll(PDO::FETCH_ASSOC);
		if ($row) {
			$row = $row[0];
			if ($row['code'])
				return $row['code'];
		}
		throw new SERIA_Exception('Failed to retrieve the code (Somebody tampering?)');
	}

	public static function rpc_getUserMeta($uid)
	{
		$user = SERIA_User::createObject($uid);
		return $user->getAllMetaExtended();
	}
	public static function rpc_updateUserMeta($uid, $updates)
	{
		SERIA_Base::debug('Syncing user metadata');
		$user = SERIA_User::createObject($uid);
		$meta = $user->getAllMetaExtended();
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
		foreach ($updates as $check) {
			if (isset($meta[$check['name']])) {
				$mydata = $meta[$check['name']];
				if ($mydata['timestamp'] < $check['timestamp']) {
					SERIA_Base::debug('User metadata '.$check['name'].' updating to '.$check['value']);
					$user->_setMetaExtended($check['name'], $check['value'], $check['timestamp']);
				} else
					SERIA_Base::debug('User metadata '.$check['name'].' is up to date.');
			} else {
				SERIA_Base::debug('Setting initial value of '.$check['name'].' to '.$check['value']);
				$user->_setMetaExtended($check['name'], $check['value'], $check['timestamp']);
			}
		}
		return $user->getAllMetaExtended();
	}

	public static function rpc_getSafeEmailAddresses($uid)
	{
		$user = SERIA_User::createObject($uid);
		return SERIA_SafeEmailUsers::getSafeEmailAddresses($user);
	}
}
