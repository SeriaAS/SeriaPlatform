<?php

/*
 * Both a reference implementation of an RPC-server, but also
 * a class that can be used to probe for access to an RPC-host
 */
class SERIA_RPCHost implements SERIA_RPCServer
{
	/**
	 * Get the authentication status on the connection level server<->server
	 * @return unknown_type
	 */
	public static function isAuthenticated()
	{
		if (!session_id())
			session_start();
		if (isset($_SESSION['rpc_authenticated']) && $_SESSION['rpc_authenticated'])
			return true;
		else
			return false;
	}
	/**
	 * Require authentication on connection server<->server
	 * @return unknown_type
	 */
	public static function requireAuthentication()
	{
		if (!SERIA_RPCHost::isAuthenticated())
			throw new SERIA_RPCAuthenticationRequiredException('Please authenticate the RPC'); /* This forces the client to authenticate */
	}
	/**
	 * Get the service name of this authenticated client. (Could be the name of the (remote/client) server owner or whatever)
	 *
	 * @return unknown_type
	 */
	public static function getServiceName()
	{
		if (!self::isAuthenticated())
			return null;
		return $_SESSION['rpc_auth_service'];
	}
	/**
	 * Get the client_id of the remote server.
	 *
	 * @return unknown_type
	 */
	public static function getClientId()
	{
		if (!self::isAuthenticated())
			return null;
		return $_SESSION['rpc_client_id'];
	}

	/**
	 * Returns wether this RPC call has been called asynchronously. (client closes the socket)
	 *
	 * @param $setTo
	 * @return unknown_type
	 */
	public static function isAsynchronous($setTo=null)
	{
		static $curIsAsynchronous = false;

		if ($setTo !== null)
			$curIsAsynchronous = $setTo;
		return $curIsAsynchronous;
	}

	/**
	 * Get the secure directory name for a framework. This
	 * checks for ..-s.
	 *
	 * @param unknown_type $name
	 * @return unknown_type
	 */
	public static function getFrameworkDirectory($name)
	{
		if (!is_dir(SERIA_ROOT.'/seria/frameworks'))
			return false;
		$d = opendir(SERIA_ROOT.'/seria/frameworks');
		if ($d === false)
			return false;
		while (($file = readdir($d)) !== false) {
			if ($file != '.' && $file != '..' && $file === $name) {
				closedir($d);
				if (is_dir(SERIA_ROOT.'/seria/frameworks/'.$file))
					return SERIA_ROOT.'/seria/frameworks/'.$file;
				else
					return false;
			}
		}
		closedir($d);
		return false;
	}

	/**
	 * Add an RPC framework.
	 *
	 * @param unknown_type $name
	 * @return unknown_type
	 */
	public function addFramework($name)
	{
		$fwdir = self::getFrameworkDirectory($name);
		if ($fwdir !== false && file_exists($fwdir.'/rpc.php')) {
			$res = require_once($fwdir.'/rpc.php');
			if (!$res)
				throw new SERIA_Exception(_t('RPC framework %NAME% denied the loading.', array('NAME' => $name)));
		} else
			throw new SERIA_Exception(_t('RPC framework %NAME% does not exist.', array('NAME' => $name)));
	}

	/*
	 * The following rpc methods are included as a reference (example) implementation:
	 */
	public static function rpc_hello($ident)
	{
		$auth = SERIA_RPCHost::getServiceName();
		if ($auth)
			$auth = ' autheticated '.$auth.' (client_id='.SERIA_RPCHost::getClientId().')';
		else
			$auth = '';
		return "Hello, $ident this is ".SERIA_HTTP_ROOT.$auth;
	}
	public static function rpc_forceAuthentication()
	{
		SERIA_RPCHost::requireAuthentication();
		return true;
	}
}
