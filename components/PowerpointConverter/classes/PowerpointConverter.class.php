<?php

class PowerpointConverter
{
	public static function convertPPTtoPNG($file)
	{
		if ($file->getMeta('powerpoint_transcode_download_complete')) {
			$relfiles = $file->getRelatedFiles('slidefrompresentation');
			if ($relfiles)
				return $relfiles;
		}
		$file->transcodeTo('powerpoint_slides');
		return true;
	}

	public static function showStatus()
	{
		$template = new SERIA_MetaTemplate();
		$transcoding = SERIA_Base::db()->query('SELECT id,file_id,status FROM {file_transcode_queue} WHERE transcoder = :transcoder', array('transcoder' => 'PowerpointSlides'))->fetchAll(PDO::FETCH_NUM);
		$files = array();
		foreach ($transcoding as $trans)
			$files[$trans[0]] = array($trans[1], $trans[2]);
		$template->addVariable('fileTranscodings', $files);
		echo $template->parse(dirname(dirname(__FILE__)).'/pages/status.php');
		die();
	}
}