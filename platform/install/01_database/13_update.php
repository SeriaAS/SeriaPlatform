<?php
/**
 * Dropping table used for article comments
 */

	SERIA_BASE::db()->exec('DROP TABLE ' . SERIA_PREFIX . '_article_comments');
?>