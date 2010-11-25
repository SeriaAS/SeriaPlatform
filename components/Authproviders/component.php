<?php

SERIA_Base::addClassPath(dirname(__FILE__)."/classes/*.class.php");

function AuthprovidersInit()
{
	$component = new SERIA_AuthprovidersComponent();
	SERIA_Components::addComponent($component);
	$component->embed();
	SERIA_Hooks::dispatch('Authproviders::inited', $component);
}
