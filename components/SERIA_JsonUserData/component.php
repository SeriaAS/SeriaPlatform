<?php

function SERIA_JsonUserData_init()
{
	SERIA_Base::addClassPath(dirname(__FILE__).'/classes/*.class.php');
	SERIA_ScriptLoader::init();
	SERIA_ScriptLoader::loadScript('SERIA-Platform-Common');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_JsonUserData/js/Cache.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_JsonUserData/js/User.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_JsonUserData/js/UserPropertyList.js?rev=2');
}