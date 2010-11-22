<?php
	SERIA_Base::db()->query('ALTER TABLE ' . SERIA_PREFIX . '_payment_dibs ADD COLUMN transactionid INT NOT NULL DEFAULT 0');
?>