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
	$fp = fopen(dirname(__FILE__).'/log.txt', 'a+');

	fwrite($fp, serialize(array($_GET, $_POST)));

	fwrite($fp, "\n1");

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
	fwrite($fp, "\n2");
		
	try
	{
		if(empty($_REQUEST['c']))
			throw new SERIA_Exception('Required parameter c (classname) not specified');
		if(empty($_REQUEST['m']))
			throw new SERIA_Exception('Required parameter m (methodname) not specified');
	fwrite($fp, "\n3");
		if(class_exists($_REQUEST['c']))
		{
			$callback = array($_REQUEST['c'], 'rpc_'.$_REQUEST['m']);
			if(!is_callable($callback))
				throw new SERIA_Exception('Unknown RPC method '.$_REQUEST['c'].'::'.$_REQUEST['m'].'.');
	fwrite($fp, "\n4");

			$r = new ReflectionClass($_REQUEST['c']);
			if(!$r->implementsInterface('SERIA_RPCServer'))
				throw new SERIA_Exception('The class '.$_REQUEST['c'].' does not implement SERIA_RPCServer.');

	fwrite($fp, "\n5");
			$arguments = array();
			$i = 0;
			while(isset($_REQUEST[$i]))
				$arguments[] = $_REQUEST[$i++];

			$result = call_user_func_array($callback, $arguments);
	fwrite($fp, serialize(array($callback, $arguments, $serialize, $result)));
			call_user_func($serialize, $result);
        }
		else
			throw new SERIA_Exception('Unknown class '.$_REQUEST['c']);
	}
	catch (SERIA_RPCAuthenticationRequiredException $e)
	{
	fwrite($fp, serialize($e));

	fclose($fp);


		call_user_func($serialize, array('error' => $e->getMessage(), 'code' => 0, 'class' => get_class($e), 'please_authenticate' => true));
	}
	catch (Exception $e)
	{
	fwrite($fp, serialize($e));

	fclose($fp);

		call_user_func($serialize, array('error' => $e->getMessage(), 'code' => $e->getCode(), 'class' => get_class($e)));
	}
