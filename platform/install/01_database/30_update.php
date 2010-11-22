<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_searchindexconfig_column ADD striphtml TINYINT NOT NULL DEFAULT 0');
?>