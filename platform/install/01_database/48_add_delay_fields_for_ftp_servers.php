<?php
	// Add columns for delaying usage of FTP files after upload.

	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_ftp_servers ADD delay INT UNSIGNED NOT NULL DEFAULT 0');
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_ftp_files ADD available INT UNSIGNED NOT NULL DEFAULT 0');
?>