<?php

$tokens = array(
	'id INTEGER NOT NULL',
	'widget_id INTEGER NOT NULL',
	'name VARCHAR(100)',
	'options TEXT',
	'PRIMARY KEY (id)',
	'FOREIGN KEY widget_key (widget_id) REFERENCES `'.SERIA_PREFIX.'_widgets` (id)'
);

SERIA_Base::db()->exec(
	'CREATE TABLE `'.SERIA_PREFIX.'_widgets_poll` ('.implode(',', $tokens).')'
);

$tokens = array(
	'id INTEGER NOT NULL',
	'poll_id INTEGER NOT NULL',
	'host_addr VARCHAR(50)',
	'vote_index INTEGER',
	'PRIMARY KEY (id)',
	'FOREIGN KEY  (poll_id) REFERENCES `'.SERIA_PREFIX.'_widgets_poll` (id)'
);

SERIA_Base::db()->exec(
	'CREATE TABLE `'.SERIA_PREFIX.'_widgets_poll_vote` ('.implode(',', $tokens).')'
);

?>