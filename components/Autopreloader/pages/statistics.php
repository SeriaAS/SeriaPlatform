<?php

require_once(dirname(__FILE__).'/../../../main.php');

SERIA_Base::pageRequires('admin');

$gui = new SERIA_Gui('Autopreloader: Statistics');

$gui->activeMenuItem('controlpanel/autopreloader/statistics');

$gui->contents(SERIA_Template::parseToString(dirname(__FILE__).'/../templates/statistics.php', array(
	'statistics' => JEP_Autopreloader::getStatistics()
)));

echo $gui->output();
