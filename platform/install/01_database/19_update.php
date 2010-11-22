<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_searchindexconfig CHANGE tablename searchtablename VARCHAR(255) NOT NULL');
?>