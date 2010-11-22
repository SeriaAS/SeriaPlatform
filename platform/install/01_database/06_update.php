<?php
	SERIA_Base::db()->exec("ALTER TABLE ".SERIA_PREFIX."_article_categories ADD 
		image_id integer DEFAULT NULL");
?>
