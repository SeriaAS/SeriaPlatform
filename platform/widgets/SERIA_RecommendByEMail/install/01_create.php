<?php

$tokens = array(
	'id INTEGER NOT NULL auto_increment',
	'widget_id INTEGER NOT NULL',
	'url VARCHAR(255)',
	'em_from VARCHAR(150)',
	'PRIMARY KEY (id)',
	'FOREIGN KEY widget_key (widget_id) REFERENCES `'.SERIA_PREFIX.'_widgets` (id)'
);

SERIA_Base::db()->exec(
	'CREATE TABLE `'.SERIA_PREFIX.'_recommend_by_email` ('.implode(',', $tokens).')'
);

?>