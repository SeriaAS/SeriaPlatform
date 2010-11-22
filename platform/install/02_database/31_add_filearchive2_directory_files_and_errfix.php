<?php

/*
 * Fix error in 30
 */
try {
	SERIA_Base::db()->exec('DROP TABLE {filedirectory}');
} catch (PDOException $e) {}
$fields = array(
	'id' => 'INT PRIMARY KEY',
	'parent_id' => 'INT',
	'pos' => 'INT',
	'name' => 'VARCHAR(100)'
);
$keys = array(
	'FOREIGN KEY (parent_id) REFERENCES {filedirectory} (id) ON DELETE CASCADE'
);

foreach ($fields as $nam => &$val)
	$val = $nam.' '.$val;
unset($val);
foreach ($keys as $val)
	$fields[] = $val;
try {
	SERIA_Base::db()->exec('CREATE TABLE {filedirectory} ('.implode(', ', $fields).') ENGINE=INNODB');
} catch (PDOException $e) {}
/*
 * Create the new table..
 */
$fields = array(
	'directory_id' => 'INT NOT NULL',
	'file_article_id' => 'INT NOT NULL'
);
$keys = array(
	'FOREIGN KEY (directory_id) REFERENCES {filedirectory} (id) ON DELETE CASCADE',
	'FOREIGN KEY (file_article_id) REFERENCES {articles} (id) ON DELETE CASCADE'
);

foreach ($fields as $nam => &$val)
	$val = $nam.' '.$val;
unset($val);
foreach ($keys as $val)
	$fields[] = $val;
try {
	SERIA_Base::db()->exec('CREATE TABLE {filedirectory_file} ('.implode(', ', $fields).') ENGINE=INNODB');
} catch (PDOException $e) {}
