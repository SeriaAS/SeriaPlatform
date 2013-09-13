<?php

class SAPI_Autokey
{
	public function __construct()
	{
		$this->hash = 'sha256';
		$this->hijackProtectionValue = session_id();
		$this->cache = new SERIA_Cache('generateAppKey');
	
		if (!$this->hijackProtectionValue)
			throw new SERIA_Exception('Protection value is not available (security)');
	}

	public static function getReturnUrl($data)
	{
		$data = unserialize($data);
		$data = $data['data'];
		$data = unserialize($data);
		return $data['requestingUrl'];
	}

	public function verify($data)
	{
		$data = unserialize($data);
		$opId = $data['opId'];
		$auth = $data['auth'];
		$data = $data['data'];

		$valid = false;
		$opKey = $this->cache->get($opId);
		if ($opKey) {
			$verify = hash_hmac($hash, $data, $opKey);
			if ($verify == $auth)
				$valid = true;
		}
		$data = unserialize($data);
		if ($this->hijackProtectionValue != $data['protection'])
			$valid = false;

		if ($valid) {
			/*
			 * Send back an auth key..
			 */
			$returnUrl = $data['requestingUrl'];
			$user = SERIA_Base::user();
			if (!$user)
				throw new SERIA_Exception('No logged in user!');
			$secret = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
			$token = new SAPI_Token();
			$token->set('user', $user);
			$token->set('secret', $secret);
			$token->set('description', 'Auto:'.$returnUrl);
			if (SERIA_Meta::save($token))
				return $secret;
		}
		return NULL;
	}
	public function getVerificationToken($requestingUrl)
	{
		$opId = sha1('genappkey'.mt_rand().mt_rand());
		$opKey = sha1(mt_rand().mt_rand().mt_rand().mt_rand());
		$this->cache->set($opId, $opKey);
		$requestingHost = new SERIA_Url($requestingUrl);
		$requestingHost = $requestingHost->getHost();
		/*
		 * This should not happen, and if it does it must be a robot or a severely misconfigured site.
		 */
		if (!$requestingHost)
			throw new SERIA_Exception('reqUrl param is invalid.', SERIA_Exception::NOT_FOUND);

		$data = array(
			'requestingUrl' => $requestingUrl,
			'protection' => $this->hijackProtectionValue
		);
		$data = serialize($data);
		$auth = hash_hmac($hash, $data, $opKey);
		$data = array(
			'opId' => $opId,
			'data' => $data,
			'auth' => $auth
		);
		return new SAPI_AutokeyVerificationObject(serialize($data));
	}
}