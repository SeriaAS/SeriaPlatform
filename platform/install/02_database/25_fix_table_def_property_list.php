<?php

$fields = array(
	'currency' => 'ISK',
	'language' => 'Intergobble',
	'transactionid' => 'Lama',
	'orderstatus' => 'Lima'
);


try {
	if (SERIA_Base::db()->insert(SERIA_PREFIX.'_property_list', array_keys($fields), $fields) !== true)
		throw new Exception('Failed');
	SERIA_Base::db()->exec('DROP TABLE '.SERIA_PREFIX.'_property_list');
} catch (Exception $e) {
}

try {
	SERIA_Base::db()->exec('CREATE TABLE `'.SERIA_PREFIX.'_property_list` (
		`owner` varchar(90) NOT NULL DEFAULT \'\',
		`name` varchar(48) NOT NULL DEFAULT \'\',
		`className` varchar(40) DEFAULT NULL,
		`value` varchar(255) DEFAULT NULL,
		PRIMARY KEY  (`owner`,`name`),
		KEY `className` (`className`,`name`,`value`)
	) ENGINE=InnoDB DEFAULT CHARSET=utf8');
} catch (Exception $e) {
	/* Ignore dupes */
}

?>