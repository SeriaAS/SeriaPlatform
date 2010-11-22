<?php
	SERIA_Base::db()->query(' ALTER TABLE ' . SERIA_PREFIX . '_searchindex_words ADD INDEX ( `word` (5) )  ');
?>