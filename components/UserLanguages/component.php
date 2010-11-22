<?php

SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');
$component = new UserLanguagesComponent();
SERIA_Components::addComponent($component);
$component->embed();

function UserLanguages_init()
{
	$component = SERIA_Components::getComponent('UserLanguagesComponent');
	SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($component, 'guiEmbed'));
}