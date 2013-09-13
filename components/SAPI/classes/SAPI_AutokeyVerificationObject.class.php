<?php

class SAPI_AutokeyVerificationObject
{
	protected $token;
	protected $data;

	public function __construct($token)
	{
		$this->token = $token;
		$this->data = unserialize($token);
		$this->data = unserialize($this->data['data']);
	}

	public function getRequestingHost()
	{
		static $requestingHost = NULL;

		if ($requestingHost !== NULL)
			return $requestingHost;

		$requestingHost = new SERIA_Url($this->data['requestingUrl']);
		$requestingHost = $requestingHost->getHost();
		return $requestingHost;
	}

	public function getToken()
	{
		return $this->token;
	}
	public function __toString()
	{
		return $this->token;
	}
}