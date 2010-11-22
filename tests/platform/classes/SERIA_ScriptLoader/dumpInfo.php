<?php

require_once(dirname(__FILE__) . '/../../../../main.php');

$gui = new SERIA_Gui('test');

ob_start();

$revdeps = SERIA_ScriptLoader_LOWLEVEL::getFullReverseDependencyTree();
print_r($revdeps);
$safeload = SERIA_ScriptLoader_LOWLEVEL::getSafeLoadOrder();
print_r($safeload);

$output = ob_get_clean();

$gui->contents($output);

echo $gui->output();

?>
