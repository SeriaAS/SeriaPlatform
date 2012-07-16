<?php
/**
*	API for remote access of functions in Seria Platform
*/
	require('../../main.php');
	SERIA_ProxyServer::privateCache(10);
	/**
	*	'e' = encoding: serialization method (php,json)
	*	'c' = class: name for RPC Server object
	*	'm' = class method
	*	'0'..'100' = class method arguments
	*/
	if(isset($_REQUEST['e']))
	{
		switch($_REQUEST['e'])
		{
			case 'query': $serialize = array('SERIA_Lib','publishQuery'); break;
			case 'json' : $serialize = array('SERIA_Lib','publishJSON'); break;
			case 'php' : $serialize = array('SERIA_Lib','publishPHP'); break;
			default : throw new Exception('Unknown serialize function');
		}
	}
	else
		$serialize = array('SERIA_Lib','publishPHP');
	try
	{
		if(empty($_REQUEST['c']))
			throw new SERIA_Exception('Required parameter c (classname) not specified');
		if(empty($_REQUEST['m']))
			throw new SERIA_Exception('Required parameter m (methodname) not specified');
		if(class_exists($_REQUEST['c']))
		{
			$callback = array($_REQUEST['c'], 'rpc_'.$_REQUEST['m']);
			if(!is_callable($callback))
				throw new SERIA_Exception('Unknown RPC method '.$_REQUEST['c'].'::'.$_REQUEST['m'].'.');

			$r = new ReflectionClass($_REQUEST['c']);
			if(!$r->implementsInterface('SERIA_RPCServer'))
				throw new SERIA_Exception('The class '.$_REQUEST['c'].' does not implement SERIA_RPCServer.');
			$arguments = array();
			$keys = array_keys($_REQUEST);
			$kMax = -1;
			foreach($keys as $key)
			{ // find last key
				if(is_numeric($key))
				{
					if($key>$kMax) $kMax = $key;
				}
			}
			for($i = 0; $i < $kMax+1; $i++)
			{ // populate arguments
				if(isset($_REQUEST[$i]))
					$arguments[$i] = $_REQUEST[$i];
				else
					$arguments[$i] = NULL;
			}

			$result = call_user_func_array($callback, $arguments);
			call_user_func($serialize, $result);
        }
		else
			throw new SERIA_Exception('Unknown class '.$_REQUEST['c']);
	}
	catch (SERIA_RPCAuthenticationRequiredException $e)
	{
		call_user_func($serialize, array('error' => $e->getMessage(), 'code' => 0, 'class' => get_class($e), 'please_authenticate' => true));
	}
	catch (Exception $e)
	{
		call_user_func($serialize, array('error' => $e->getMessage(), 'code' => $e->getCode(), 'class' => get_class($e), 'file' => $e->getFile()));
	}
