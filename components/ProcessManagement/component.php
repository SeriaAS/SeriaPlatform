<?php

SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');

$component = new ProcessManagementComponent();
SERIA_Components::addComponent($component);
$component->embed();
