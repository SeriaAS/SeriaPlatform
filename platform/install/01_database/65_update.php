<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_users DROP INDEX '.SERIA_PREFIX.'_users_username_idx, DROP INDEX '.SERIA_PREFIX.'_users_email_idx');
?>
