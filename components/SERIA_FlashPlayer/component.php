<?php

SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');
$component = new SERIA_FlashPlayerComponent();
SERIA_Components::addComponent($component);
$component->embed();

function SeriaPlayer_init()
{
	$component = SERIA_Components::getComponent('SERIA_FlashPlayerComponent');
	SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($component, 'guiEmbed'));
}
