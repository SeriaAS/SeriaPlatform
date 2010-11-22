<?php
try {
	SERIA_Base::db()->exec('ALTER TABLE {cache} MODIFY name VARCHAR(128)');
} catch (PDOException $e) {

}
