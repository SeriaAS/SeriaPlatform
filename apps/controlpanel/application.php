<?php
	/**
	*	Add local classes to the class path
	*/
	SERIA_Base::addClassPath(SERIA_ROOT."/seria/apps/controlpanel/classes/*.class.php");

	/**
	*	Register this application
	*/
	SERIA_Applications::addApplication($seriaControlPanel = new SERIA_ControlPanelApplication());
	function controlpanelInit()
	{
		global $seriaControlPanel;
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($seriaControlPanel, 'guiEmbed'));
	}

        $components = glob(SERIA_ROOT.'/seria/apps/controlpanel/components/*', GLOB_ONLYDIR);
        foreach($components as $component)
        {
                require($component.'/component.php');
        }
