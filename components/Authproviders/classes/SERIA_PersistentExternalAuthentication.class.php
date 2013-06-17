<?php

class SERIA_PersistentExternalAuthentication implements SERIA_RPCServer
{
	const PING_INTERVAL = 120;

	public static function authenticatedExternally()
	{
	}
	public static function externalAuthenticationRefresh(SERIA_ExternalAuthprovider $provider)
	{
	}
	public static function forceExternalAuthenticationRefresh(SERIA_ExternalAuthprovider $provider)
	{
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