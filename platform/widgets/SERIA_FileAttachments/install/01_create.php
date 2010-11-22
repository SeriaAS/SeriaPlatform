<?php

$tokens = array(
	'id INTEGER NOT NULL auto_increment',
	'widget_id INTEGER NOT NULL',
	'file_id INTEGER NOT NULL',
	'PRIMARY KEY (id)',
	'FOREIGN KEY widget_key (widget_id) REFERENCES `'.SERIA_PREFIX.'_widgets` (id)',
	'FOREIGN KEY file_key (file_id) REFERENCES `'.SERIA_PREFIX.'_files` (id) ON DELETE CASCADE'
);

SERIA_Base::db()->exec(
	'CREATE TABLE `'.SERIA_PREFIX.'_widgets_attachments` ('.implode(',', $tokens).')'
);

?>