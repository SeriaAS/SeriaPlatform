<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_systemstatusmessages CHANGE time time DATETIME NOT NULL');
?>