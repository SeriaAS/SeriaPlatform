<?php
	SERIA_Base::db()->query(' ALTER TABLE ' . SERIA_PREFIX . '_searchindex_words CHANGE `word` `word` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_swedish_ci NOT NULL  ');
?>