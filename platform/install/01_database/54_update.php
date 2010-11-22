<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_systemstatusmessages` ADD UNIQUE (`key`)');
?>