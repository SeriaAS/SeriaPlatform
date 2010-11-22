<?php
	require(dirname(__FILE__).'/../../main.php');

	function seria_api_error($code, $message)
	{
		seria_api_output(array('code'=>$code, 'message'=>$message));
	}

	function seria_api_output($data)
	{
		// TODO: Accept GET param "fmt", either XML or JSON. The called file does not have to know, since everything is handled by SERIA_Lib::publishJSON or SERIA_Lib::publishXML
		//if(!isset($_GET['fmt']))
		//{
		//	seria_api_error(1, 'Format not specified. Use GET parameter fmt=json');
		//}
		SERIA_Lib::publishJSON($data);
		die();
	}
