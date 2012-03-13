<?php
	/**
	*	This file is included by default for multi site installations of Seria Platform.
	*
	*	It does not work trough the SERIA_Meta API.
	*/
	if(!defined('SERIA_MULTISITE_DOMAIN'))
		throw new SERIA_Exception('SERIA_MULTISITE_DOMAIN not defined.');

	// required to connect to database, but should not be defined by anybody
	define('SERIA_DB_DSN', SERIA_DB_TYPE.':host='.SERIA_DB_HOST.';port='.(defined('SERIA_DB_PORT')?SERIA_DB_PORT:'3306').';dbname='.SERIA_DB_NAME);

	require_once(SERIA_ROOT.'/seria/platform/components/SERIA_Multisite/classes/SERIA_Multisite.class.php');

	function multisiteInit() {
		// select the correct site
		if($_SERVER['HTTP_HOST']===NULL)
			return false;
		$host = $_SERVER['HTTP_HOST'];
		if(substr($host,0,4)==='www.')
			$host = substr($host,4);
		if(SERIA_Multisite::isMaster())
			return false;

		// connect to database
		$db = SERIA_Base::db();
		$site = $db->query("SELECT * FROM {sites} WHERE domain=?", array($host))->fetch(PDO::FETCH_ASSOC);
		if(!$site)
		{
			$siteAlias = $db->query("SELECT * FROM {sites_aliases} WHERE domain=?", array($host))->fetch(PDO::FETCH_ASSOC);
			if(!$siteAlias)
				throw new SERIA_Exception('Site with domain name '.$host.' was not found.', SERIA_Exception::NOT_FOUND);
			if($siteAlias['domainType']=='alias')
			{
				$site = $db->query("SELECT * FROM {sites} WHERE id=?", array($siteAlias['siteId']))->fetch(PDO::FETCH_ASSOC);
				$site['alias'] = $siteAlias;
				if(!$site)
				{
					$db->exec("DELETE FROM {sites_aliases} WHERE id=?", array($siteAlias['id']));
					throw new SERIA_Exception('An alias was configured but the site does not exist. The alias was deleted.');
				}
			}
			else if($siteAlias['domainType']=='forwarder')
			{
				header("Location: http".((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') ? '' : 's')."://".$site['domain']);
				die();
			}
			else throw new SERIA_Exception('Unknown error');
		}


		// switch to the correct database
		//TODO: Perform a complete reconnect and support separate user accounts. Now the user have complete access to go back to our main database.
		try {
			$db->exec('USE '.$site['dbName']);
		}
		catch (PDOException $e) {
			if($e->getCode()=="42000")
			{ // the database does not exist. Try to create it!
				try {
					$db->exec('CREATE DATABASE '.$site['dbName']);
				} catch (PDOException $e) {
					if($e->getCode()=="42000")
						throw new SERIA_Exception('Unable to create the "'.$site['dbName'].'" database for the site. I must have the CREATE database privilege, or you must create the database and grant me access to it.', SERIA_Exception::ACCESS_DENIED);
					throw $e;
				}
			}
		}
		define('SERIA_SITE_ID', md5($site['dbName]));
		return $site;
	}

	$GLOBALS['seriamultisite'] = $site = multisiteInit();

	if($site===false)
	{
		// this is the default site. Continue with the default _config.php file
		require(dirname(dirname(dirname(__FILE__))).'/_config.php');
		define('SERIA_SITE_ID', md5(SERIA_DB_NAME));
	}
	else
	{
		if(!defined('SERIA_HTTP_ROOT'))
		{
			if(isset($site['alias']))
				define('SERIA_HTTP_ROOT', "http".((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') ? '' : 's')."://".$site['alias']['domain']);
			else
				define('SERIA_HTTP_ROOT', "http".((empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']==='off') ? '' : 's')."://".$site['domain']);
		}
		define('SERIA_FILES_ROOT', SERIA_ROOT.'/sites/'.$site['domain'][0].'/'.$site['domain'].'/files');
		define('SERIA_FILES_HTTP_ROOT', SERIA_HTTP_ROOT.'/sites/'.$site['domain'][0].'/'.$site['domain'].'/files');
		define('SERIA_EMAIL_FROM', 'no-reply@'.$site['domain']);
		define('SERIA_TIMEZONE', $site['timezone']);
		define('SERIA_CURRENCY', $site['currency']);
		define('SERIA_ERROR_EMAIL', $site['errorMail']);

		if(file_exists(SERIA_ROOT.'/sites/'.$site['domain'][0].'/'.$site['domain'].'/designs')) {
			define('SERIA_TEMPLATE_ROOT', SERIA_ROOT.'/sites/'.$site['domain'][0].'/'.$site['domain'].'/designs');
		        define('SERIA_TEMPLATE_HTTP_ROOT', SERIA_HTTP_ROOT.'/sites/'.$site['domain'][0].'/'.$site['domain'].'/designs');
		} else {
			define('SERIA_TEMPLATE_ROOT', SERIA_ROOT.'/sites/default_design');
			define('SERIA_TEMPLATE_HTTP_ROOT', SERIA_HTTP_ROOT.'/sites/default_design');
		}

	}
