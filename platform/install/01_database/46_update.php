<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_ftp_server_filetypes ADD type VARCHAR(25) DEFAULT \'include\'');
?>