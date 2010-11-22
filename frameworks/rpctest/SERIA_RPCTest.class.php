<?php

class SERIA_RPCTest implements SERIA_RPCServer
{
	public static function rpc_hello()
	{
		SERIA_RPCHost::requireAuthentication();
	}
	public static function rpc_heavyWorkload()
	{
		SERIA_RPCHost::requireAuthentication();
		$i = 100000000;
		while (--$i > 0);
	}
}