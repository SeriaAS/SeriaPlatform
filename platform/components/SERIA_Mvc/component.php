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
	*
	* @author Frode Børli
	* @package platform
	*/
	class SERIA_MvcManifest {
		const SERIAL = 1;
		const NAME = 'mvc';

		/**
		*	Hook into SERIA_MetaTemplate, for example for adding custom tags.
		*	Your callback will be called with the template object as first parameter.
		*	$callback($template);
		*/
		const TEMPLATE_EXTEND_HOOK = 'SERIA_MvcManifest::TEMPLATE_EXTEND_HOOK';

		public static $classPaths = array(
			'classes/*.class.php',
		);
	}

	function SERIA_MvcInit()
	{
		// Listen to autoloads so that we can update database model when class files change.
		SERIA_Hooks::listen(SERIA_MetaTemplateHooks::EXTEND, 'SERIA_MetaTemplate_extend');

		// Add menu items from manifests according to the router rules
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, 'SERIA_Mvc_gui');

		if(defined('SERIA_TEMPLATE_ROOT')) 
			SERIA_Hooks::listen(SERIA_PlatformHooks::ROUTER_FAILED, 'SERIA_MetaTemplate_router');
	}

	/* DEPRECATED */
	define('SERIA_METATEMPLATE_EXTEND','SERIA_MvcManifest::TEMPLATE_EXTEND_HOOK');

	/* DEPRECATED */
	class SERIA_MetaTemplateHooks {
		const EXTEND = 'SERIA_MvcManifest::TEMPLATE_EXTEND_HOOK';
	}

	function SERIA_Mvc_gui($gui) {
		$manifests = SERIA_Manifests::getAllManifests();
		foreach($manifests as $name => $reflector)
		{
			// Manifest::$classPaths
			try {
				$menus = $reflector->getStaticPropertyValue('menu');
				if($menus) foreach($menus as $menuName => $spec)
				{
					$parts = explode("/", $menuName);
					$app = array_shift($parts);
					$url = SERIA_Meta::manifestUrl($app, implode("/", $parts));
					$gui->addMenuItem($menuName, $spec['title'], $spec['description'], $url, !empty($spec['icon']) ? SERIA_HTTP_ROOT.substr(dirname($reflector->getFileName()), strlen(SERIA_ROOT)).'/'.$spec['icon'] : false, !empty($spec['weight']) ? $spec['weight'] :  0);
				}
			}
			catch (ReflectionException $null) {}
		}
	}

	/**
	*	This router handler handles /seria/[manifest] routes, as well as routes referring to 
	*	php-files inside of SERIA_TEMPLATE_ROOT. Examples:
	*
	*	The SERIA_MultisiteManifest is declared in /seria/platform/components/SERIA_Multisite/component.php and has
	*	const NAME = 'multisite';
	*
	*	/seria/multisite		Will look for /seria/platform/components/SERIA_Multisite/pages/index.php
	*	/seria/multisite/edit		Will look for /seria/platform/components/SERIA_Multisite/pages/edit.php
	*	/seria/multisite/edit/site	Will look for /seria/platform/components/SERIA_Multisite/pages/edit/site.php
	*/
	function SERIA_MetaTemplate_router($route)
	{
		if(strpos($route, "seria/api/rest/")===0)
		{ // support RESTful access of SERIA_MetaObjects trough an URL matching /seria/rest/ClassName
			$route = trim(substr($route, 15), "/");
			$parts = explode("/", $route);
			if(sizeof($parts)==0) // could provide a list of class names
				return;
			require(dirname(__FILE__).'/includes/restApi.php');
			die();
		}
		else if(strpos($route, "seria/")===0)
		{ // Access pages in components and applications trough an url matching /seria/[manifestname]
			$parts = explode("/", $route);
			if(sizeof($parts)==0)
				return;
			if($manifest = SERIA_Manifests::getManifest($parts[1]))
			{ // we have a manifest matching this name
				$root = dirname($manifest->getFileName())."/pages";
				if(is_dir($root))
				{
					array_shift($parts);
					array_shift($parts);

					if(sizeof($parts)>0)
					{
						$path = $root."/".implode("/", $parts).'.php';
					}
					else
					{
						$path = $root.'/index.php';
					}

					if(file_exists($path) && !is_dir($path))
					{
						SERIA_Gui::activeMenuItemHint(substr($route, 6));
						$template = new SERIA_MetaTemplate();
						if(file_exists($root.'/_common.php'))
							$template->includeFile($root.'/_common.php');
						echo $template->parse($path);
						die();
					}
				}
			}
		}


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
		$tpl->addVariableCallback('Meta', 'SERIA_MetaTemplate_MetaTemplateVariable');
		$tpl->addTagCompiler('s:grid', 'SERIA_MetaTemplate_sGrid');
		$tpl->addTagCompiler('/s:grid', 'SERIA_MetaTemplate_sGridClose');
		$tpl->addTagCompiler('s:submit', 'SERIA_MetaTemplate_sSubmit');
		$tpl->addTagCompiler('s:label', 'SERIA_MetaTemplate_sLabel');
		$tpl->addTagCompiler('s:field', 'SERIA_MetaTemplate_sField');
		$tpl->addTagCompiler('s:form', 'SERIA_MetaTemplate_sForm');
		$tpl->addTagCompiler('/s:form', 'SERIA_MetaTemplate_sFormClose');
		$tpl->addTagCompiler('s:loop', 'SERIA_MetaTemplate_sLoop');
		$tpl->addTagCompiler('/s:loop', 'SERIA_MetaTemplate_sLoopClose');
		$tpl->addTagCompiler('s:a', 'SERIA_MetaTemplate_sA');
		$tpl->addTagCompiler('/s:a', 'SERIA_MetaTemplate_sAClose');
	}

	/**
	*	Returns a variable to be used within meta templates to navigate important meta information
	*/
	function SERIA_MetaTemplate_MetaTemplateVariable()
	{
		return new SERIA_MetaTemplateVariable();
	}

	/**
	*	Extends SERIA_MetaTemplate with the following:
	*	<s:form for="{site}" action="edit">
	*/
	function SERIA_MetaTemplate_sForm($tag, $templateFileName)
	{
		if(!$tag->get('for'))
			return '<?php echo SERIA_MetaTemplate::displayError(\'Required parameters for s:form is "for".\'); ?>';
		$action = SERIA_MetaTemplate::attributeToConstant($tag->get('for'));


		SERIA_MetaTemplate::push('s:form');

		$code = '<?php

try {
	$sForm = '.$action.';
} catch (SERIA_Exception $e) {
	echo SERIA_MetaTemplate::displayError($e->getMessage());
}

if(!($sForm instanceof SERIA_ActionForm))
{
	echo SERIA_MetaTemplate::displayError("s:form expects a ActionForm as parameter.");
	$sForm = NULL;
}

if($sForm) echo $sForm->begin();

?>';

return $code;

/*



		$code = '<?php ';
		try {
			$for = SERIA_MetaTemplate::attributeToConstant($tag->get('for'));
			try {
				$code .= '
try {
	if(!('.$for.' instanceof SERIA_ActionForm)) {
		echo SERIA_MetaTemplate::displayError(\'The variable '.$tag->get('for').' is not a SERIA_ActionForm.\');
	else
		$___NULL = true;
} catch (SERIA_Exception $e) {
	echo SERIA_MetaTemplate::displayError($e->getMessage());
	$___NULL = false;
}
				';
			}
			catch (SERIA_Exception $e) {
				SERIA_MetaTemplate::displayError('.
			}
		} catch (SERIA_MetaTemplateException $e) {
			return '<?php echo SERIA_MetaTemplate::displayError('.var_export($e->getMessage(), true).'); ?>';
		}

		$code .= 'echo $sForm->begin(); ?'.'>';
		return $code;
*/
	}

	/**
	*	Extends SERIA_MetaTemplate with the following:
	*	</s:form>
	*/
	function SERIA_MetaTemplate_sFormClose($tag, $templateFileName)
	{
		try {
			SERIA_MetaTemplate::pop('s:form');
		} catch (SERIA_MetaTemplateException $e) {
			return '<?php echo SERIA_MetaTemplate::displayError('.var_export($e->getMessage(), true).'); ?>';
		}
		return '<?php echo $sForm->end(); $sForm = NULL; ?'.'>';
	}

	/**
	*	Extends SERIA_MetaTemplate with the following:
	*	<s:submit />
	*	<s:submit label='{"Some Label"|_t}' />
	*/
	function SERIA_MetaTemplate_sSubmit($tag, $templateFileName)
	{
		if($tag->get('label'))
			$code = '<?php echo $sForm->submit('.SERIA_MetaTemplate::attributeToConstant($tag->get('label'), $templateFileName).'); ?>';
		else
			$code = '<?php echo $sForm->submit(); ?>';
		return $code;
	}

	/**
	*	Exteds SERIA_MetaTemplate with the following:
	*	<s:label for="fieldname" />
	*/
	function SERIA_MetaTemplate_sLabel($tag, $templateFileName)
	{ // <s:label for='description'>
		if(!SERIA_MetaTemplate::inStack('s:form'))
			return '<?php echo SERIA_MetaTemplate::displayError("s:label not allowed outside of s:form"); ?>';

		return '<?php echo ($sForm ? $sForm->label('.SERIA_MetaTemplate::attributeToConstant($tag->get('for'), $templateFileName).') : SERIA_MetaTemplate::displayError("s:label without active form")); ?>';
	}

	/**
	*	Exteds SERIA_MetaTemplate with the following:
	*	<s:field for="fieldname" />
	*/
	function SERIA_MetaTemplate_sField($tag, $templateFileName)
	{ // <s:label for='description'>
		return '<?php echo $sForm->field('.SERIA_MetaTemplate::attributeToConstant($tag->get('for'), $templateFileName).', '.var_export($tag->getProperties(), true).'); ?>';
	}


	function SERIA_MetaTemplate_sLoop($tag, $templateFileName)
	{
		if($tag->get('trough') && $tag->get('as'))
		{
			SERIA_MetaTemplate::push('s:loop');
			return '<'.'?php
$___TMP = '.SERIA_MetaTemplate::attributeToConstant($tag->get('trough')).';
if($___TMP === NULL)
	SERIA_MetaTemplate::displayError(\'The variable "'.$tag->get('trough').'" does not exist!\');
else if(!is_array($___TMP) && !($___TMP instanceof Traversable))
	SERIA_MetaTemplate::displayError(\'The variable "'.$tag->get('trough').'" is not traversable using s:loop!\');
else foreach('.SERIA_MetaTemplate::attributeToConstant($tag->get('trough')).' as '.($tag->get('key')?SERIA_MetaTemplate::attributeToVariable($tag->get('key')).'=>':'').SERIA_MetaTemplate::attributeToVariable($tag->get('as')).') { ?'.'>';
		}
		else
		{
			return '<?php echo SERIA_MetaTemplate::displayError(\'Required parameters for s:loop is "trough" and "as".\'); ?>';
		}

	}

	function SERIA_MetaTemplate_sLoopClose($tag, $templateFileName)
	{
		try {
			SERIA_MetaTemplate::pop('s:loop');
		} catch (SERIA_MetaTemplateException $e) {
			return '<?php echo SERIA_MetaTemplate::displayError('.var_export($e->getMessage(), true).'); ?>';
		}
		return '<'.'?php } ?'.'>';
	}

	function SERIA_MetaTemplate_sA($tag, $templateFileName)
	{
		$tag->tagName = 'a';
//		return $tag;
		$tag->set('href', '<?php echo '.SERIA_MetaTemplate::attributeToConstant($tag->get('href')).'; ?>', true);

		return $tag->__toString();
	}

	function SERIA_MetaTemplate_sAClose($tag, $templateFileName)
	{
		return '</a>';
	}
