<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_payment_dibs ADD COLUMN `key` VARCHAR(255) NOT NULL DEFAULT \'\'');
?>