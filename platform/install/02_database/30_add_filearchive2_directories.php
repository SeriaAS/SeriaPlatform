<?php
/**
*	30.1.2010, Frode: Error in syntax for FOREIGN KEY (column name of target not specified). The update script should have failed so
*	there should be no problem in updating this file.
*/
$fields = array(
	'id' => 'INT PRIMARY KEY',
	'parent_id' => 'INT',
	'pos' => 'INT',
	'name' => 'VARCHAR(100)'
);
$keys = array(
	'FOREIGN KEY (parent_id) REFERENCES {filedirectory}(id)'
);

foreach ($fields as $nam => &$val)
	$val = $nam.' '.$val;
unset($val);
foreach ($keys as $val)
	$fields[] = $val;
try {
	SERIA_Base::db()->exec('CREATE TABLE {filedirectory} ('.implode(', ', $fields).') ENGINE=InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci');
} catch (PDOException $e) {}
