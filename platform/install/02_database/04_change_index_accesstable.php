<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_guid_accesstable` DROP INDEX `object_guid` ,
		ADD UNIQUE `object_guid` ( `object_guid` , `access_guid` , `right_id` ) ');
?>