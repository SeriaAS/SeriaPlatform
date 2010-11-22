<?php
return;
	SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/SERIA_AmazonCloudServiceProvider/classes/*.class.php');

	function SERIA_AmazonCloudServiceProviderInit()
	{
		SERIA_Hooks::listen(SERIA_CloudServiceHooks::GET_PROVIDERS, array('SERIA_AmazonCloudServiceProvider','getProviders'));
		SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array('SERIA_AmazonCloudServiceProvider','guiEmbed'));
	}
