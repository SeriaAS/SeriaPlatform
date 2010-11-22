<?php
	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_files CHANGE 
		ftp_server_id ftp_server_id int(11) default NULL");

	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_timing_statistics ADD 
		timing_seconds float default NULL");

?>
