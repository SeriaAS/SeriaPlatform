<?php
	/**
	*	Seria Cloud Management Service. A Service API for managing virtual servers and computers.
	*/
	/**
	*	Declare hooks
	*/
	class SERIA_CloudServiceHooks
	{
		const GET_PROVIDERS = 'SERIA_CLOUDSERVICE_GET_PROVIDERS';
	}

	/**
	*	Initialize
	*/
	function SERIA_CloudServiceInit()
	{
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array('SERIA_CloudService','guiEmbed'));
	}

	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_CloudService/classes/*.class.php');

