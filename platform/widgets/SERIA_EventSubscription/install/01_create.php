<?php

$tokens = array(
	'id INTEGER NOT NULL auto_increment',
	'widget_id INTEGER NOT NULL',
	'name VARCHAR(100)',
	'address VARCHAR(100)',
	'zip VARCHAR(20)',
	'city VARCHAR(50)',
	'phone VARCHAR(50)',
	'orgNum VARCHAR(50)',
	'email VARCHAR(256)',
	'company VARCHAR(100)',
	'billingAdress VARCHAR(100)',
	'billingZip VARCHAR(20)',
	'billingCity VARCHAR(50)',
	'PRIMARY KEY (id)',
	'FOREIGN KEY widget_key (widget_id) REFERENCES `'.SERIA_PREFIX.'_widgets` (id)'
);

SERIA_Base::db()->exec(
	'CREATE TABLE `'.SERIA_PREFIX.'_widgets_event_subscription` ('.implode(',', $tokens).')'
);

?>