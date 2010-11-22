<?php
	SERIA_Base::db()->exec('UPDATE '.SERIA_PREFIX.'_users set username=NULL,email=NULL where enabled=0');
?>
