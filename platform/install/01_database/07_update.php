<?php
	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_articles ADD 
		rating_counter integer NOT NULL DEFAULT 0");
?>