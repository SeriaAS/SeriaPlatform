<?php
//TODO: Frode: Review this code.
/**
*	Notes:
*	1. 	Nested calls to serialize method should not be performed, as it assumes knowledge of how every
*		serializing function works. Look at: Line #176
*	2.	Echoing the result from serialize means no Content-Type header
*	3.	Template is disabled at line #15, then overridden in line #166.
*	3.	The charset is never specified, it should be utf-8.
*/
/**
*	API for remote access of functions in Seria Platform
*/
	require('../../main.php');
	SERIA_Template::disable();

	class SERIA_RPCAuthenticationRequiredException extends SERIA_Exception {
	}

	if(isset($_REQUEST['e']))
	{
		switch($_REQUEST['e'])
		{
			case 'query': $serialize = 'http_build_query';
			case 'json' : $serialize = array('SERIA_Lib','toJSON'); break;
			case 'php' : $serialize = 'serialize'; break;
			default : throw new Exception('Unknown serialize function');
		}
	}
	else
		$serialize = 'serialize';

	try
	{
		if (isset($_GET['handshake']) && isset($_GET['client_id'])) {
			/*
			 * Client indicates which key it has access to by encrypting a nonce, together with a hash of the nonce
			 */
			$client = SERIA_Base::db()->query('SELECT * FROM {rpc_clients} WHERE client_id = :client_id', array(':client_id' => $_GET['client_id']))->fetch(PDO::FETCH_ASSOC);
			if (!$client)
				throw new SERIA_Exception('Authentication failure (server<->server)');
			$try_ident = $client['name'];
			$try_key = $client['client_key'];
			$auth_request = SERIA_CryptoBlowfish::decrypt($try_key, base64_decode($_GET['handshake']), 'ecb');
			/*
			 * Expect 20 bytes of random data, folowed by a sha1 hash of the characters,
			 * any trailing garbage will be ignored
			 */
			$randomd = substr($auth_request, 0, 20);
			$test_hash = sha1($randomd, true);
			$hashblk = substr($auth_request, 20, 20);
			if ($test_hash == $hashblk) {
				/* Match, respond with a session key and IV */
				$IV = sha1($test_hash, true);
				$sessionKey = sha1(mt_rand().mt_rand().mt_rand().mt_rand(), true);
				$sessIV = sha1(mt_rand().mt_rand().mt_rand().mt_rand(), true);
				if (!session_id())
					session_start();
				$_SESSION['rpc_authenticated'] = false;
				unset($_SESSION['rpc_authenticated']);
				$_SESSION['rpc_auth_key'] = $sessionKey;
				$_SESSION['rpc_auth_iv'] = $sessIV;
				$_SESSION['rpc_auth_service'] = $try_ident;
				$_SESSION['rpc_client_id'] = $client['client_id'];
				$auth = http_build_query(array('sessionKey' => base64_encode($sessionKey), 'IV' => base64_encode($sessIV)));
				$auth = base64_encode($auth);
				echo base64_encode(SERIA_CryptoBlowfish::encrypt($try_key, $auth, 'cbc', SERIA_CryptoBlowfish::createIVFromData('cbc', $IV)));
				die();
			}
			throw new SERIA_Exception('Authentication failure (server<->server)');
		}

		/**
		*	'framework' = include_framework: This framework must have a rpc.php file.
		*	'e' = encoding: serialization method (php,json)
		*	'c' = class: name for RPC Server object
		*	'm' = class method
		*	'0'..'100' = class method arguments
		*/
		if(empty($_REQUEST['c']))
			throw new SERIA_Exception('Required parameter c (classname) not specified');
		if(empty($_REQUEST['m']))
			throw new SERIA_Exception('Required parameter m (methodname) not specified');

		/*
		 * Authenticated requests:
		 */
		if (isset($_REQUEST['sig']) && !empty($_REQUEST['sig'])) {
			if (!session_id())
				session_start();
			if (isset($_SESSION['rpc_auth_key']) && isset($_SESSION['rpc_auth_iv'])) {
				$_SESSION['rpc_authenticated'] = false;
				$sessionKey = $_SESSION['rpc_auth_key'];
				$IV = $_SESSION['rpc_auth_iv'];
				/*
				 * Verify the signature..
				 */
				if (isset($_GET['sig']))
					$req = $_GET;
				else
					$req = $_POST;
				unset($req['sig']);
				$reqstr = http_build_query($req);
				$reqhash = sha1($reqstr, true);
				$bin_sig = base64_decode($_REQUEST['sig']);
				$sigval = base64_decode(SERIA_CryptoBlowfish::decrypt($sessionKey, $bin_sig, 'cbc', SERIA_CryptoBlowfish::createIVFromData('cbc', $IV)));
				if ($reqhash === $sigval) {
					/*
					 * Update the IV
					 */
					$_SESSION['rpc_auth_iv'] = sha1($IV, true);
					/*
					 * Set authenticated
					 */
					$_SESSION['rpc_authenticated'] = true;
				} else
					throw new SERIA_Exception('Authentication failure.');
			} else
				throw new SERIA_Exception('Auth could not be verified (no session)');
		}
		if (isset($_REQUEST['framework']) && !empty($_REQUEST['framework']))
			SERIA_RPCHost::addFramework($_REQUEST['framework']);
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
			$result = array('return' => call_user_func($serialize, $result));
			if (SERIA_RPCHost::isAuthenticated())
				$result['authenticated'] = true;
			echo call_user_func($serialize, $result);
		}
		else
			throw new SERIA_Exception('Unknown class '.$_REQUEST['c']);
	}
	catch (SERIA_RPCAuthenticationRequiredException $e)
	{
		echo call_user_func($serialize, array('error' => $e->getMessage(), 'code' => 0, 'class' => get_class($e), 'please_authenticate' => true));
	}
	catch (Exception $e)
	{
		echo call_user_func($serialize, array('error' => $e->getMessage(), 'code' => $e->getCode(), 'class' => get_class($e), 'traceString' => $e->getTraceAsString()));
	}
