<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_article_url_generators` ADD `key` VARCHAR( 255 ) NOT NULL ,
	                                                                                   ADD `description` VARCHAR( 255 ) NOT NULL ;');
?>