<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_files` ADD FOREIGN KEY ( `ftp_server_id` ) REFERENCES `' . SERIA_PREFIX . '_ftp_servers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>