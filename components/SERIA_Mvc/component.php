<?php
	/**
	* The SERIA_MVC-component provides classes for proper model-view-controller pattern in Seria
	* Platform. Although the implementation may differ slightly from other implementations of MVC,
	* it is a pattern in which;
	*
	* 1. View only talk to the Model to retrieve information.
	* 2. View interacts with Controllers trough Action objects.
	* 3. Controller only talk to Model and other API's.
	* 4. Controller only interacts with the View trough Action objects.
	*
	* Generally; 
	* 1. Create PHP-files that present data from SERIA_MetaObjects.
	* 2. These PHP-files can retrieve SERIA_Action objects from SERIA_IController classes.
	* 3. These 
	*/

	/**
	*	Hook into SERIA_MetaTemplate, for example for adding custom tags.
	*	Your callback will be called with the template object as first parameter.
	*	$callback($template);
	*/
	define('SERIA_METATEMPLATE_EXTEND','SERIA_METATEMPLATE_EXTEND');

	class SERIA_MetaTemplateHooks {
		const EXTEND = 'SERIA_METATEMPLATE_EXTEND';
	}

	// Add classpath to make these classes available.
	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_Mvc/classes/*.class.php');

	function SERIA_Mvc_init()
	{
		// Listen to autoloads so that we can update database model when class files change.
		SERIA_Hooks::listen(SERIA_MetaTemplateHooks::EXTEND, 'SERIA_MetaTemplate_extend');

		if(defined('SERIA_TEMPLATE_ROOT')) 
			SERIA_Hooks::listen(SERIA_PlatformHooks::ROUTER_FAILED, 'SERIA_MetaTemplate_router');
	}

	function SERIA_MetaTemplate_router($route)
	{
		$route = trim($route, "/ \t");
		if($route=='')
			$route = 'index';

		if(trim($route, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789/')!=='')
			throw new SERIA_Exception('Invalid route');

		if(is_dir(SERIA_TEMPLATE_ROOT.'/'.$route))
			$route = $route.'/index';

		if(file_exists(SERIA_TEMPLATE_ROOT.'/'.$route.'.php'))
		{
			$path = ini_get('include_path');
			ini_set('include_path', SERIA_TEMPLATE_ROOT);
			$template = new SERIA_MetaTemplate();
			echo $template->parse(SERIA_TEMPLATE_ROOT.'/'.$route.'.php');
			ini_set($path);
			die();
		}
	}

	function SERIA_MetaTemplate_extend($tpl)
	{
		$tpl->addTagCompiler('s:loop', 'SERIA_MetaTemplate_sLoop');
		$tpl->addTagCompiler('/s:loop', 'SERIA_MetaTemplate_sLoopClose');
	}

	function SERIA_MetaTemplate_sLoop($tag)
	{
		if($tag->get('trough') && $tag->get('as'))
		{
			return '<'.'?php /*FRODE*/ foreach('.SERIA_MetaTemplate::_compileVariable($tag->get('trough')).' as '.($tag->get('key')?SERIA_MetaTemplate::_compileVariable($tag->get('key'), true).'=>':'').SERIA_MetaTemplate::_compileVariable($tag->get('as'),true).') { ?'.'>';
		}
		else
			throw new SERIA_Exception('Required parameters for the s:loop tag are "trough" and "as".');

	}

	function SERIA_MetaTemplate_sLoopClose($tag)
	{
		return '<'.'?php } ?'.'>';
	}
