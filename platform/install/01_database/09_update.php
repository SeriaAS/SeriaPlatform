<?php
	SERIA_BASE::db()->exec('ALTER TABLE ' . SERIA_PREFIX . '_files ADD updated_at DATETIME');
?>