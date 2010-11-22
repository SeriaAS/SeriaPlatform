<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_searchindexconfig_column ADD keyword VARCHAR(50) NOT NULL DEFAULT \'\'');
?>