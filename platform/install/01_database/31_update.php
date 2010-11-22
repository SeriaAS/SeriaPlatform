<?php
	SERIA_Base::db()->query('ALTER TABLE `' . SERIA_PREFIX . '_searchindex_words` CHANGE `word` `word` VARBINARY( 255 ) NOT NULL  ');
?>