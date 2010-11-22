<?php
/**
 *	This file simply redirects the user to the application with the highest weight.
 *	@package SeriaPlatform
 */
	require(dirname(__FILE__).'/main.php');

	if(isset($_GET['page']))
	{
		//sanitize ?page= query string
		$page = isset($_GET['page']) ? $_GET['page'] : 'default';
		preg_match('|[a-zA-Z0-9_\-]+|', $page, $matches);
		$page = str_replace(array('_', '-'),array('/', '_'),$matches[0]).'.php';
		$templateFilename = dirname(__FILE__).'/'.$page;
		SERIA_Base::debug('Trying to open template: '.$templateFilename);
		if(!file_exists($templateFilename))
			$page = 'errors/404.php';

		$template = new SERIA_MetaTemplate();
		$template->addVariableCallback('user', array('SERIA_Base','user'));
		echo $template->parse($templateFilename);
	}
	else
	{
		$gui = new SERIA_Gui(_t("Seria Platform"));

		$menuItem = false;
		$menuItems = $gui->getMenuItems();
		foreach ($menuItems as $item) {
			if ($menuItem === false || $item['weight'] > $menuItem['weight'])
				$menuItem = $item;
		}
		SERIA_Template::disable();
		header("Location: ".$menuItem['url']);
		die();
	}
