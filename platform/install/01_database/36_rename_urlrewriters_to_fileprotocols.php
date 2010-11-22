<?php
	SERIA_Base::db()->query('RENAME TABLE `' . SERIA_PREFIX . '_ftp_urlrewriters`  TO `' . SERIA_PREFIX . '_ftp_fileprotocols`');
?>