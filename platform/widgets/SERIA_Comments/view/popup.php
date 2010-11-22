<?php

require(dirname(__FILE__).'/form_inc.php');

if (count($_POST) && count($errors) == 0) {
	?><script type='text/javascript'>SERIA.Popup.returnValue(true);</script><?php
}

?>