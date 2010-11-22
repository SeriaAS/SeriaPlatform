<?php

$tokens = array(
	'id INTEGER NOT NULL auto_increment',
	'widget_id INTEGER NOT NULL',
	'author VARCHAR(50)',
	'avatar_id INTEGER',
	'title VARCHAR(200)',
	'text TEXT',
	'PRIMARY KEY (id)',
	'FOREIGN KEY widget_key (widget_id) REFERENCES `'.SERIA_PREFIX.'_widgets` (id)'
);

SERIA_Base::db()->exec(
	'CREATE TABLE `'.SERIA_PREFIX.'_widgets_comments` ('.implode(',', $tokens).')'
);


?>