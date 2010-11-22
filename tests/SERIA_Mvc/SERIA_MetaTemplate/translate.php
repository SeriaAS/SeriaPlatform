<?php

require(dirname(__FILE__).'/../../../main.php');

$template = new SERIA_MetaTemplate();

echo $template->parse(dirname(__FILE__).'/templates/translate.php');
