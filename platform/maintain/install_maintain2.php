<?php
	function install_maintain2() {
		$installer = new SERIA_Installer();
		$installer->runUpdates();
		return 'Ok';
	}
?>
