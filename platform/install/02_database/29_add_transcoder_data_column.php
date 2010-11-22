<?php 

SERIA_Base::db()->exec('ALTER TABLE {file_transcode_queue} ADD COLUMN data TEXT');

?>