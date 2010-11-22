<?php

require(dirname(__FILE__).'/../../../../main.php');

if (isset($_POST['message'])) {
	SERIA_Async::call(array('SERIA_Base', 'debug'), $_POST['message']);
	header('Location: '.SERIA_Filesystem::getUrlFromPath(__FILE__));
}

$gui = new SERIA_Gui('Test async by sending debug messages to maintain');

ob_start();
?>
<form method='post'>
	<div>
		<label for='tmessage'>Message: </label>
		<input id='tmessage' name='message' type='text' value='' %XHTML_CLOSE_TAG%>
	</div>
	<div>
		<button type='submit'>Submit</button>
	</div>
</form>
<?php
$gui->contents(ob_get_clean());

echo $gui->output();
