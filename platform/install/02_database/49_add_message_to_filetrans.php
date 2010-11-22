<?php

$dataDefs = array(
	'message TEXT DEFAULT NULL'
);

SERIA_Base::db()->exec('ALTER TABLE {file_transcode_queue} ADD COLUMN ('.implode(', ', $dataDefs).')');
