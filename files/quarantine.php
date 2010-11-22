<?php
	require_once(dirname(__FILE__)."/../main.php");
	SERIA_Template::disable();
	SERIA_Base::preventCaching();

	if (!isset($_GET['fileid']))
		throw new Exception('No id for download');
	$file = false;
	try {
		$file = SERIA_File::createObject($_GET['fileid']);
	} catch (Exception $e) {
		$file = false;
	}
	if (!isset($_GET['key']))
		throw new Exception('No key for download');
	if (sha1($_SERVER['REMOTE_ADDR'].$file->get('filename')) !== $_GET['key'])
		$file = false;
	if ($file === false)
		throw new Exception('Bad key for file download (Generic auth err encap)');

	$filename = $file->get('filename');

	$pi = pathinfo($filename);
	$rows = SERIA_Base::db()->query('SELECT mimetype FROM '.SERIA_PREFIX.'_filetypes WHERE extension = '.SERIA_Base::db()->quote($pi['extension']))->fetchAll();
	if (!count($rows)) {
		$ctype = SERIA_Lib::getContentType($filename);
		if (!$ctype)
			throw new Exception('Unknown file type: Don\'t know the mime.');
	} else
		$ctype = $rows[0]['mimetype'];

	$path = SERIA_UPLOAD_ROOT.'/'.$filename;

	header('Content-Disposition: inline; filename=' . urlencode('randname'.mt_rand().'.'.$pi['extension']));

	header('Content-Type: '.$ctype);

	die(file_get_contents($path));
?>