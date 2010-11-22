<?php

require_once(dirname(__FILE__) . '/../../../../main.php');

$gui = new SERIA_Gui('test');

$layout = array(
	'columns' => array(
		array(
			'width' => 100
		),
		array(
			'width' => 100
		),
		array(
		)
	)
);

$divbuilder = new SERIA_DivBuilder($layout);

$divbuilder->addContent('sdfhsdsdfgdg<br>dfhdfgdf<br>dfgdfgdfgdf');
$divbuilder->addContent('sdfsdfsdf<br>sdgsdfsdf<br>fgdfg<br>dfgdfgdfgd<br>fsdgdfgdfg');
$divbuilder->addContent('sdgsdfgsd');

$divbuilder->addContent('dfgdfg');
$divbuilder->addContent('dfgdfgdf');
$divbuilder->addContent('dfgdfgd');

$gui->contents($divbuilder->output());

echo $gui->output();

?>
