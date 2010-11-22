<?php

require_once(dirname(__FILE__).'/../../../main.php');

$gui = new SERIA_Gui('Autopreloader: Statistics');

$gui->activeMenuItem('controlpanel/autopreloader');

$gui->contents('');

echo $gui->output();
