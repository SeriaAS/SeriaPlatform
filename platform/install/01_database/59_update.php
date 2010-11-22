<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_incoming_ftp_servers ADD title VARCHAR(255) NOT NULL DEFAULT \'\'');
?>