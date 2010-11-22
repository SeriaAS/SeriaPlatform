<?php

/*
 * Default page for client event tasks
 */

require_once(dirname(__FILE__).'/../../../../main.php');

if (!isset($_GET['widget_id']))
	die();
$widget = SERIA_Widget::createObject($_GET['widget_id']);

$gui = new SERIA_Gui(_t('Event subscription'));

$gui->contents($widget->output('client'));

echo $gui->output();

?>
