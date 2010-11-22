<?php

SERIA_Base::addClassPath(dirname(__FILE__)."/classes/*.class.php");

function WindowsLiveAuthprovider_init()
{
	$component = new WindowsLiveAuthproviderComponent();
	SERIA_Components::addComponent($component);
	$component->embed();
}
