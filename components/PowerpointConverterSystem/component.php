<?php
//require(dirname(__FILE__).'/classes/PowerpointConverterSystem.class.php');

SERIA_Base::addClassPath(SERIA_ROOT.'/seria/components/PowerpointConverterSystem/classes/*.class.php');

SERIA_Hooks::listen('seria_maintain', array('PowerpointConverterSystem', 'fromMaintain'));
