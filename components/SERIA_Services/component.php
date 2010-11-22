<?php
	/**
	*	Hooks
	*/
	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_Services/classes/*.class.php');
	function SERIA_ServicesInit()
	{
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array('SERIA_Services', 'guiEmbed'));
	}

