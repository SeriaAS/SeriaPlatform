<?php
	/**
	*	Component for working with multisite support in Seria Platform.
	*
	*	@author Frode Børli
	*	@version 1.0
	*	@package seriaplatform
	*/
	class SERIA_MultisiteManifest
	{
		const SERIAL = 7;
		const NAME = 'multisite';

		public static $classPaths = array(
			'classes/*.class.php',
		);

		public static $metaClasses = array(
			'SERIA_Site',
			'SERIA_SiteAlias',
		);

		public static $database = array(
			'drops' => array(
			),
		);
	}

	function SERIA_MultisiteInit() {
		SERIA_Hooks::listen(SERIA_MetaTemplateHooks::EXTEND, 'SERIA_Multisite_extend');
	}

	function SERIA_Multisite_extend($tpl) {
		$tpl->addVariableCallback('site', 'SERIA_Multisite_site');
	}

	function SERIA_Multisite_site() {
		if(isset($GLOBALS['seriamultisite']))
			return $GLOBALS['seriamultisite'];

		return NULL;
	}

	// calls maintain.php on every website
	function SERIA_Multisite_maintain() {
		if(SERIA_Multisite::isMaster())
		{
			// call maintain.php on every site, except for master site
			$sites = SERIA_Base::db()->query("SELECT * FROM {sites} ORDER BY maintainDate LIMIT 1")->fetchAll(PDO::FETCH_ASSOC);
			foreach($sites as $site)
			{
				$url = parse_url('http://'.$site['domain']."/seria/platform/maintain.php");
				$s = fsockopen($_SERVER["SERVER_ADDR"], $_SERVER["SERVER_PORT"], $eNum, $eStr, 1);
				if($s)
				{
					@fwrite($s, "GET ".$url["path"]."?quick=1&multisite=1 HTTP/1.1\r\nHost: ".$url["host"]."\r\nConnection: close\r\n\r\n");
					@fclose($s);
				}
				SERIA_Base::db()->query("UPDATE {sites} SET maintainDate=NOW() WHERE id=".$site["id"]);
			}
		}
	}

	SERIA_Hooks::listen(SERIA_MAINTAIN_HOOK, 'SERIA_Multisite_maintain');
