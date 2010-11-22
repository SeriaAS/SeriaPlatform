<?php

SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');

$component = new GoogleAnalyticsComponent();
SERIA_Components::addComponent($component);
$component->embed();

function GoogleAnalytics_init()
{
	$component = SERIA_Components::getComponent('GoogleAnalyticsComponent');
	$component->init();
}
