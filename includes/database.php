<?php
die("OK");
	/**
	 *	CONNECT TO DATABASE
	 */
	function seria_databaseConnect() {
		try {
			if(class_exists('PDO') && !defined('DISABLE_PDO')) {
				SERIA_Base::db(new PDO(SERIA_DB_DSN, SERIA_DB_USER, SERIA_DB_PASSWORD));
			} else if(SERIA_DB_TYPE == 'mysql') {
				require_once(SERIA_ROOT."/seria/platform/compatability/PDO_MySQLi.class.php");
				SERIA_Base::db(new PDO_MySQLi(new mysqli(SERIA_DB_HOST, SERIA_DB_USER, SERIA_DB_PASSWORD, SERIA_DB_NAME, SERIA_DB_PORT)));
			}
			else
				throw new SERIA_Exception('Unknown database type "'.SERIA_DB_TYPE.'".');
		} catch (Exception $e) {
			throw new Exception("Unable to connect to database.");
		}

		$db = SERIA_Base::db();
		
		/**
		 * Special calls for MySQL
		 */
		if($db->getAttribute(PDO::ATTR_DRIVER_NAME) == "mysql") {
			$db->exec("SET NAMES utf8");
			$db->exec("SET time_zone = '".date("P")."'");
			// REQUIRED FOR SOME VERSIONS OF MYSQL
// This has been fixed in SERIA_DB			$db->setAttribute(PDO::MYSQL_ATTR_DIRECT_QUERY, 1);
		}
		
		/**
		 *	STANDARDIZE ENVIRONMENT
		 */
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	}
	
	seria_databaseConnect();
?>
