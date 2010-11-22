<?php

class SERIA_PersistentExternalAuthentication implements SERIA_RPCServer
{
	const PING_INTERVAL = 120;

	public static function authenticatedExternally()
	{
		if (!session_id())
			return;
		$_SESSION['AUTHPROVIDERS_REMOTE_SID_PING'] = time();
	}
	public static function externalAuthenticationRefresh(SERIA_ExternalAuthprovider $provider)
	{
		if (!session_id())
			return;
		if (!isset($_SESSION['AUTHPROVIDERS_REMOTE_SID'])) {
			SERIA_Base::debug('Externally authenticated session lacks a remote-sid!');
			return;
		}
		$esid = $_SESSION['AUTHPROVIDERS_REMOTE_SID'];
		if (isset($_SESSION['AUTHPROVIDERS_REMOTE_SID_PING']))
			$ping = $_SESSION['AUTHPROVIDERS_REMOTE_SID_PING'];
		else
			$time = 0;
		if (time() > ($ping + self::PING_INTERVAL)) {
			SERIA_Base::debug('Timer has expired, we are going to ping the authentication provider..');
			$rpc = SERIA_RPCClient::connect($provider->get('remote'), 'SERIA_PersistentExternalAuthentication');
			if (!$rpc->queryAuthenticationStatus($esid)) {
				/*
				 * Session is gone on remote, we'll have to log you out..
				 */
				SERIA_Base::debug('Remote does not have this user logged in...');
				SERIA_Base::user(NULL);
				return;
			}
			$_SESSION['AUTHPROVIDERS_REMOTE_SID_PING'] = time();
		} else
			SERIA_Base::debug('Time is ok, no need to ping authentication provider yet..');
	}

	public static function rpc_queryAuthenticationStatus($sid)
	{
		SERIA_RPCHost::requireAuthentication();
		$session = new SeriaPlatformSession($sid);
		if ($session->getUser())
			return true; /* Login still valid */
		else
			return false; /* Login has expired */
	}
}