<?php
	require('../common.php');
	SERIA_Template::disable();
	
	$data = $_POST['data'];
	if (!$data) {
		$result = array('status' => 0);
	} else {
		$cometSystem = new SERIA_CometSystem();
		$data = $cometSystem->fromHttpQuery($data);
	}
	
	SERIA_Lib::publishJSON($result);
?>