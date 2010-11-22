<?php

if (defined('NDLA_ROAM_AUTH_COOKIE') && NDLA_ROAM_AUTH_COOKIE) {
	SERIA_Base::addClassPath(dirname(__FILE__)."/classes/*.class.php");
	$component = new NDLA_RoamAuthCookieComponent(NDLA_ROAM_AUTH_COOKIE);
	SERIA_Components::addComponent($component);
	$component->embed();
}