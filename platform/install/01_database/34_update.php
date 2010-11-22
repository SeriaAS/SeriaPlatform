<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_ftp_files` ADD FOREIGN KEY ( `file_id` ) REFERENCES ' . SERIA_PREFIX . '_files (`id`) ON DELETE CASCADE ON UPDATE CASCADE ;');
?>