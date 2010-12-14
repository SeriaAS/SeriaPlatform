<?php
	/**
	*	SERIA_Gui Component
	*	@author Frode Børli
	*	@package platform
	*/
	class SERIA_GuiManifest {
		const SERIAL = 1;
		const NAME = 'gui';

		const EMBED_HOOK = 'SERIA_GuiManifest::EMBED_HOOK';

		public static $classPaths = array('classes/*.class.php');
	}

	/**
	*	Hook for integrating with the user interface in the administrative section of Seria Platform.
	*	Listeners are passed one argument containing an instance of SERIA_Gui
	*	@deprecated
	*/
	class SERIA_GuiHooks {
		const EMBED = 'SERIA_GuiManifest::EMBED_HOOK';
	}

	function SERIA_GuiInit()
	{
		SERIA_Hooks::listen(SERIA_MvcManifest::TEMPLATE_EXTEND_HOOK, 'SERIA_Gui_MetaTemplate_extend');
		SERIA_Hooks::listen(SERIA_PLATFORM_BOOT_COMPLETE_HOOK, 'SERIA_Gui_PlatformBootComplete');
	}

	function SERIA_Gui_MetaTemplate_extend($template)
	{
		$template->addTagCompiler('s:gui', array('SERIA_Gui', 'sGuiTag'));
		$template->addTagCompiler('/s:gui', array('SERIA_Gui', 'sGuiTagClose'));
		$template->addTagCompiler('s:script', array('SERIA_ScriptLoader', 'sScriptTag'));
	}

	function SERIA_Gui_PlatformBootComplete()
	{
		/**
		 * 	Attempt to autoload certain javascripts that are required for some applications.
		 */
//		SERIA_ScriptLoader::loadScript('jQuery');
//		SERIA_ScriptLoader::loadScript('jQuery-ui');
//		SERIA_ScriptLoader::loadScript('jQuery-treeview');

		// From version 2 theese scripts aren't automatically included. There are cases where these scripts generates javascript-errors when included
		if (SERIA_COMPATIBILITY < 2) {
			if(SERIA_Base::user() !== false)
				SERIA_ScriptLoader::loadScript('SERIA-Platform-Private');
			else
				SERIA_ScriptLoader::loadScript('SERIA-Platform-Public');

			SERIA_ScriptLoader::loadScript('SERIA-Platform-Common');
		}
	}
