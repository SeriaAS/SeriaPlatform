<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_searchindex_words CHANGE id id MEDIUMINT UNSIGNED NOT NULL AUTO_INCREMENT');
?>