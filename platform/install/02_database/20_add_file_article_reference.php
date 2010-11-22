<?php
try {
	SERIA_Base::db()->exec('
		ALTER TABLE '.SERIA_PREFIX.'_files ADD (file_article_id integer)
	');
} catch (PDOException $e) {}
?>
