<?php
/*
 * This file tests tabs through SERIA_Gui
 * Possible points of failure includes:
 *  * SERIA_GUI
 *  * SERIA_ScriptLoader
 *  * ui.tabs
 *  * ui.core/ui.base
 *  * jQuery
 */

require_once(dirname(__FILE__).'/../../../../../common.php');

SERIA_ScriptLoader::loadScript('jQuery-ui-tabs', false, '1.6rc5');

$gui = new SERIA_Gui('test ui.tabs');

$tabs = new SERIA_BMLTabs(array(
	array(
		'name' => 'Tab 1',
		'contents' => 'Testcont 1'
	),
	array(
		'name' => 'Tab 2',
		'contents' => 'Testcont 2'
	)
));

$contents = $tabs->output();

$gui->contents($contents);

echo $gui->output();

?>
