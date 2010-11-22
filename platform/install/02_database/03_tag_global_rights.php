<?php
	SERIA_Base::db()->query('UPDATE ' . SERIA_PREFIX . '_rights SET guidkey=\'global\' WHERE guidkey=\'\'');
?>