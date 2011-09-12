<?php

SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');

function FacebookAuthproviderInitHook()
{
	SERIA_Authproviders::addProviderClass('FacebookAuthprovider');
}
function FacebookAuthproviderInit()
{
	$authproviders = SERIA_Components::getComponent('seria_authproviders');
	if ($authproviders !== false)
		FacebookAuthproviderInitHook();
	else
		SERIA_Hooks::listen('Authproviders::inited', 'FacebookAuthproviderInitHook');
}
