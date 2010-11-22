<?php

$component = SERIA_Components::getComponent('windows_live_authprovider_component');
if (!file_exists($component->getPrivateCodegenDir()))
	mkdir($component->getPrivateCodegenDir(), 0755);
