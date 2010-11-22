<?php
	function search_indexer_log_message($message) {
		echo $message;
	}

	/* This file run the search_maintain function, and redirect back to this page processing new records. */

	require('../common.php');
	$manual_maintain = true;
	require('maintain.php');
	set_time_limit(1000);
	ob_implicit_flush(true);
	
	run_search_maintain(true);
?>
<script type="text/javascript">
	setTimeout('window.location.reload()', 1000);
</script>