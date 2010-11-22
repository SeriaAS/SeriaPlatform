<?php
try {
	SERIA_Base::db()->exec('ALTER TABLE {cache} CHANGE name name VARCHAR(128)');
} catch (PDOException $e) {

}
