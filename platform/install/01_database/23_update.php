<?php
	SERIA_Base::db()->query('RENAME TABLE ' . SERIA_PREFIX . '_memcached  TO ' . SERIA_PREFIX . '_memcaches;');
?>