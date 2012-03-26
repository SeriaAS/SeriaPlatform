<?php

class SERIA_ExternalReq2Token
{
	protected $token;
	protected $error = null;

	const VALID_TIME = 300; /* 5min */

	public function __construct($strToken)
	{
		$this->token = array();
		SERIA_Url::parse_str($strToken, $this->token);
	}

	public function __toString()
	{
		return http_build_query($this->token, '', '&');
	}

	/**
	 *
	 * Create an authenticated token (hmac)
	 * @param array $data Optional data to send with the token
	 * @param string $hash Hash function to use (Default: sha256).
	 */
	public static function createToken($data = array(), $hash = 'sha256')
	{
		$data['time'] = time();
		$data['salt'] = substr(md5(mt_rand()), 0, 6);
		$data = array(
			'hash' => $hash,
			'data' => http_build_query($data, 'num', '&')
		);
		$key = SERIA_AuthprovidersComponent::getMacKey();
		if (!$key)
			throw new SERIA_Exception('A key should have been generated automatically');
		$data['hmac'] = hash_hmac($hash, $data['data'], $key);
		return new self(http_build_query($data, '', '&'));
	}

	/**
	 *
	 * Validate the token. If the token is invalid the function will store the error (you can retrieve it by
	 * calling the getError method). Otherwise if the error is more severe it will throw an exception.
	 *
	 * @throws SERIA_Exception
	 * @return boolean Returns true if the token is valid otherwise false.
	 */
	public function validateToken()
	{
		$algos = hash_algos();
		if (!in_array($this->token['hash'], $algos))
			throw new SERIA_Exception('Unsupported hash algorithm: '.$params['apiHash']);
		$data = array();
		SERIA_Url::parse_str($this->token['data'], $data);
		if (!isset($data['time']))
			throw new SERIA_Exception('Timestamp is required');
		$time = time();
		if ($data['time'] > $time) {
			$this->error = 'Timestamp is invalid (in the future)';
			if (SERIA_DEBUG)
				throw new SERIA_Exception($this->error);
			return false;
		}
		if (($data['time'] + self::VALID_TIME) < $time) {
			$this->error = 'Timestamp is invalid (too old)';
			if (SERIA_DEBUG)
				throw new SERIA_Exception($this->error);
			return false;
		}
		$key = SERIA_AuthprovidersComponent::getMacKey();
		if (!$key)
			throw new SERIA_Exception('A key should have been generated automatically');
		$check = hash_hmac($this->token['hash'], $this->token['data'], $key);
		$valid = ($check && $check == $this->token['hmac']);
		if (!$valid) {
			$this->error = 'HMAC is invalid';
			if (SERIA_DEBUG)
				throw new SERIA_Exception($this->error);
			return false;
		}
		return $valid;
	}

	/**
	 *
	 * Get the data supplied with the token (typically time and salt).
	 */
	public function getData()
	{
		$data = array();
		SERIA_Url::parse_str($this->token['data'], $data);
		return $data;
	}
	/**
	 *
	 * Get the error-message from validation.
	 * @param string Error-message.
	 */
	public function getError()
	{
		return $this->error;
	}
}
