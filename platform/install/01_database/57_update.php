<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_ftp_servers ADD storageusage INT UNSIGNED NOT NULL DEFAULT 0, ADD storageupdate INT UNSIGNED NOT NULL DEFAULT 0, ADD filecount INT UNSIGNED NOT NULL DEFAULT 0');
?>