<?php

require_once(dirname(__FILE__).'/../../../main.php');

if (!isset($_GET['id']) || !file_exists(SERIA_UserLoginXml::getUserXmlFilename($_GET['id']))) {
	SERIA_Template::override('text/plain', '');
	return;
}

SERIA_Template::override('text/xml', SERIA_UserLoginXml::getUserXml($_GET['id']));
