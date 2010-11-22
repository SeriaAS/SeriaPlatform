<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_systemstatusmessages DROP INDEX `key`');
?>