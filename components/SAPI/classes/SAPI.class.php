<?php

class SAPI
{
	public static function buildAuthenticatedMessage($params, $secret)
	{
		$hash = 'sha256';
		$params['apiTime'] = time();
		$params['apiSalt'] = substr(md5(mt_rand()), 0, 6);
		$params['apiHash'] = $hash;
		$message = http_build_query($params, '', '&');
		$params['apiAuth'] = hash_hmac($hash, $message, $secret);
		return $params;
	}
	public static function getAuthenticatedMessage($params, $secret)
	{
		if (!self::isAuthenticatedMessage($params))
			return false;
		$time = time();
		$low = $time-600;
		$high = $time+60;
		if ($params['apiTime'] < $low || $params['apiTime'] > $high)
			throw new SERIA_Exception('Bad timestamp or clock sync!');
		$algos = hash_algos();
		if (!in_array($params['apiHash'], $algos))
			throw new SERIA_Exception('Unsupported hash algorithm: '.$params['apiHash']);
		$auth = $params['apiAuth'];
		unset($params['apiAuth']);
		$message = http_build_query($params, '', '&');
		$check = hash_hmac($params['apiHash'], $message, $secret);
		if ($check == $auth) {
			unset($params['apiTime']);
			unset($params['apiSalt']);
			unset($params['apiHash']);
			return $params;
		} else
			return false;
	}
	public static function isAuthenticatedMessage($params)
	{
		if (isset($params['apiTime']) && isset($params['apiSalt']) &&
		    isset($params['apiHash']) && isset($params['apiAuth']) &&
		    $params['apiTime'] && $params['apiSalt'] && $params['apiHash'] && $params['apiAuth'])
			return true;
		else
			return false;
	}

	/**
	 *
	 * Call a SAPI-method as if it was a POST-request, and most likely it is because this method is being used for handling that.
	 * @param string $class The SAPI-class
	 * @param string $method The method to call.
	 * @param array $postParameters The posted parameters.
	 * @param array $getParameters The query parameters.
	 */
	public static function post($class, $method, array $postParameters, array $getParameters = array())
	{
		if ($method !== NULL)
			$method = new SAPI_Method($class, $method);
		else
			$method = new SAPI_SERIA_Mvc($class);
		return $method->post($postParameters, $getParameters);
	}
	/**
	 *
	 * Call a SAPI-method as if it was a GET-request, and most likely it is because this method is bein used for handling that.
	 * @param string $class The SAPI-class
	 * @param string $method The method to call.
	 * @param array $getParameters The query parameters
	 */
	public static function get($class, $method, array $getParameters)
	{
		if ($method !== NULL)
			$method = new SAPI_Method($class, $method);
		else
			$method = new SAPI_SERIA_Mvc($class);
		return $method->get($getParameters);
	}
	/**
	 *
	 * Call a SAPI-method as if it was a PUT-request, and most likely it is because this method is bein used for handling that.
	 * @param string $class The SAPI-class
	 * @param string $method The method to call.
	 * @param array $getParameters The query parameters
	 */
	public static function put($class, $method, array $getParameters)
	{
		$method = new SAPI_Method($class, $method);
		return $method->put($getParameters);
	}
	/**
	 *
	 * Call a SAPI-method as if it was a DELETE-request, and most likely it is because this method is bein used for handling that.
	 * @param string $class The SAPI-class
	 * @param string $method The method to call.
	 * @param array $getParameters The query parameters
	 */
	public static function delete($class, $method, array $getParameters)
	{
		$method = new SAPI_Method($class, $method);
		return $method->delete($getParameters);
	}

	public static function js()
	{
		SERIA_ScriptLoader::loadScript('jQuery');
		SERIA_ScriptLoader::loadScript('SERIA-Platform-Public');
		$sapijs = SERIA_Meta::assetUrl('SAPI', 'sapi.js');
		if ($sapijs instanceof SERIA_Url)
			$sapijs = $sapijs->__toString();
		ob_start();
		?><script type='text/javascript' src="<?php echo htmlspecialchars($sapijs); ?>"></script><?php
		return ob_get_clean();
	}
}