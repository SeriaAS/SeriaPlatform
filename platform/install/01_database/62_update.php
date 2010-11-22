<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_ftp_servers ADD filetypepattern VARCHAR(255) NOT NULL DEFAULT \'\'');
?>