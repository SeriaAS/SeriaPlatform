<?php

require_once(dirname(__FILE__) . "/../../../../main.php");

$tableSettings = array(
	'fields' => array(
		array(
			'title' => '1',
			'type' => 'number'
		),
		array(
			'title' => '2',
			'type' => 'number'
		),
		array(
			'title' => '3',
			'type' => 'number'
		),
		array(
			'title' => '4',
			'type' => 'number'
		)
	)
);

$table = new SERIA_Table($tableSettings);

for ($i = 0; $i < 5; $i++) {
	$rowdata = array($i, $i+5);
	$sortdata = array(($i * 13) % 7, (pow($i, 7) % 13));
	$rowdata[2] = $sortdata[0];
	$rowdata[3] = $sortdata[1];
	$table->addRow($rowdata, $sortdata);
}

echo $table->output();

?>