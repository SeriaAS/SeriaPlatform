<?php
	require_once('../common.php');
	SERIA_Template::disable();
	$instanceId = intval($_GET["id"]);
$fp = fopen('test.txt', 'a');
fwrite($fp, print_r($data, true)."hmm".print_r($_GET, true));
fclose($fp);

	if($instanceId>0)
	{
		$file = SERIA_File::createObject($instanceId);

		$data = array(
			'id' => $file->get('id'),
		);
		
		if(!$_GET['thumbnail'])
			$_GET['thumbnail'] = '400x400';

		if(isset($_GET['thumbnail']))
		{
			list($width,$height) = explode('x', $_GET['thumbnail']);
			if($width<1 || $height<1 || $width*$height>1000000)
				seria_api_error(1, 'Invalid thumbnail size');

			try {
				$thumbnail = $file->getThumbnailURL($width, $height);
				$data['thumbnail'] = $thumbnail;
			} catch (Exception $null) {}
		}
		seria_api_output($data);
	}
