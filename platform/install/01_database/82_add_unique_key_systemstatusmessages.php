<?php
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_systemstatusmessages SET `key`=concat(md5(id), id)');
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_systemstatusmessages ADD UNIQUE (`key`)');
?>