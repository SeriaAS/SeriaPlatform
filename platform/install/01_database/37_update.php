<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_servers`
		ADD `request_path` VARCHAR( 255 ) NOT NULL,
		ADD `request_host` VARCHAR( 255 ) NOT NULL');
?>