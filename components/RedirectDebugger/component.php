<?php

if (!SERIA_DEBUG || !defined('SERIA_REDIRECT_DEBUG') || !SERIA_REDIRECT_DEBUG)
	return;

SERIA_Base::addClassPath(dirname(__FILE__)."/classes/*.class.php");

$component = new RedirectDebuggerComponent();
SERIA_Components::addComponent($component);
$component->embed();
