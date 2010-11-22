<?php

require(dirname(__FILE__).'/../../../main.php');

$metaTemplate = new SERIA_MetaTemplate();
$component = SERIA_Components::getComponent('simplesamlcomponent');
if (!$component)
	throw new SERIA_Exception('SimpleSAML component is not loaded.');
echo $metaTemplate->parse($component->getTemplateFilename('loginFailed'));
