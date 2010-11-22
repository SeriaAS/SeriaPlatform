<?php
/**
*	API for remote access of functions in Seria Platform
*/
	require('../../main.php');
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
			$i = 0;
			while(isset($_REQUEST[$i]))
				$arguments[] = $_REQUEST[$i++];

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
		call_user_func($serialize, array('error' => $e->getMessage(), 'code' => $e->getCode(), 'class' => get_class($e)));
	}
