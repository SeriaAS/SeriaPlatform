<?php
try {
SERIA_Base::db()->query('
CREATE TABLE `' . SERIA_PREFIX . '_filetypes` (
  `id` int(11) NOT NULL auto_increment,
  `mimetype` varchar(100) default NULL,
  `extension` varchar(5) default NULL,
  `groupname` varchar(25) default NULL,
  `no` varchar(25) default NULL,
  `icon` varchar(80) default NULL,
  `restricted_upload` tinyint(1) default 0,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;
');
}
catch (PDOException $e)
{
}
$filetypes = array(
	array('image/pjpeg',					'jpg',	'images',		'Bilder',			'documentJPEG.gif'),
	array('image/gif',						'gif',	'images',		'Bilder',			'documentGIF.gif'),
	array('text/html',						'html',	'htmldocs',		'HTML- filer',		'documentHTML.gif'),
	array('application/msword',				'doc',	'textdocs',		'Tekstdokumenter',	'documentWORD.gif'),
	array('application/octet-stream',		'doc',	'textdocs',		'Tekstdokumenter',	'documentWORD.gif'),
	array('application/pdf',				'pdf',	'pdfdocs',		'PDF- filer',		'documentPDF.gif'),
	array('text/html',						'wpsm',	'htmldocs',		'HTML- filer',		'documentWPSM.gif'),
	array('application/excel',				'xls',	'spreadsheet',	'Regneark',			'documentXLS.gif'),
	array('audio/mpeg',						'mp3',	'audio',		'Lydfiler',			'documentMP3.gif'),
	array('application/vnd.ms-powerpoint', 	'ppt',	'powerpoint',	'PPT- filer',		'documentPPT.gif'),
	array('image/jpeg',						'jpg',	'images',		'Bilder',			'documentJPEG.gif'),
	array('jpeg',							'jpg',	'images',		'Bilder',			'documentJPEG.gif'),
	array('text/plain',						'txt',	'textdocs',		'Tekstdokumenter',	'documentTXT.gif'),
	array('application/x-zip-compressed',	'zip',	'zipfiles',		'ZIP- filer',		'documentZIP.gif'),
	array('message/rfc822',					'mht',	'powerpoint',	'Presentasjon',		'documentPPT.gif'),
	array('message/rfc822',					'mhtm',	'powerpoint',	'Presentasjon',		'documentPPT.gif'),
	array('image/png',						'png',	'images',		'Bilder',			'documentJPEG.gif'),
	array('application/x-shockwave-flash',	'swf',	'video',		'Video',			'documentSWF.gif'),
	array('application/vnd.ms-excel',		'xls',	'spreadsheet',	'Regneark',			'documentXLS.gif'),
	array('image/x-png',					'png',	'images',		'Bilder',			'documentGIF.gif'),
	array('video/mpeg',						'mpg',	'video',		'Video',			'documentJPEG.gif'),
	array('video/mpeg',						'mpeg',	'video',		'Video',			'documentJPEG.gif'),
	array('application/vnd.ms-powerpoint',	'pps',	'powerpoint',	'Presentasjon',		'documentPPT.gif'),
	array('image/pjpeg',					'jpeg',	'images',		'Bilder',			'documentJPEG.gif'),
	array('application/msword',				'rtf',	'textdocs', 	'Tekstdokumenter',	'documentWORD.gif'),
	array('image/x-citrix-gif',				'gif',	'images',		'Bilder',			'documentGIF.gif'),
	array('image/x-citrix-pjpeg',			'jpg',	'images',		'Bilder',			'documentJPEG.gif'),
	array('application/octet-stream',		'eps',	null,			'Bilder',			'documentPDF.gif'),
	array('application/octet-stream',		'psd',	null,			'Bilder',			'documentPDF.gif'),
	array('application/octet-stream',		'exe',	'exefiles',		'Kjorbar fil',		'documentEXE.gif'),
	array('video/avi',						'avi',	'video',		'Video',			'documentJPEG.gif'),
	array('audio/mid',						'mid',	'audio',		'Lydfiler',			'documentMID.gif'),
	array('application/octet-stream',		'dcr',	'flash',		'Flash- filer',		null),
	array('video/x-ms-wmv',					'wmv',	'video',		'Video',			'documentJPEG.gif'),
	array('text/plain',						'html',	'htmldocs',		'HTML- filer',		'documentHTML.gif'),
	array('application/x-zip-compressed',	'jar',	'jarfiles',		'JAR- filer',		'documentZIP.gif'),
	array('application/zip',				'zip',	'zipfiles',		'ZIP- filer',		'documentZIP.gif'),
	array('application/stuffit',			'zip',	'zipfiles',		'ZIP- filer',		'documentZIP.gif'),
	array('application/octet-stream',		'rar',	'rarfiles',		'RAR- filer',		'documentZIP.gif'),
	array('application/pdf',				'ai',	'vector',		'Vektor- filer',	'documentAI.gif'),
	array('audio/mp3',						'mp3',	'audio',		'Lydfiler',			'documentMP3.gif'),
	array('application/octet-stream',		'flv',	'video',		'Video',			'documentSWF.gif'),
	array('video/quicktime',				'mov',	'video',		'Video',			'documentMOV.gif'),
	array('text/x-vcard',					'vcf',	'calendar',		'Kalender',			null),
	array('text/x-vcard',					'vcard','calendar',		'Kalender',			null),
	array('video/mp4',						'mp4',	'video',		'Video',			'documentJPEG.gif'),
	array('application/octet-stream',		'fla',	'video',		'Video',			'documentJPEG.gif'),
	array('application/vnd.openxmlformats-officedocument.wordprocessingml.document','docx','textdocs','Tekstdokumenter','documentWORD.gif'),
	array('application/octet-stream',		'm4a',	'audio',		'Lydfiler',			'documentMP3.gif'),
	array('audio/m4a',						'm4a',	'audio',		'Lydfiler',			'documentMP3.gif'),
	array('application/octet-stream',		'xls',	'spreadsheet',	'Regneark',			'documentXLS.gif'),
	array('application/octet-stream',		'vcf',	'calendar',		'Kalender',			null),
	array('application/octet-stream',		'vcard','calendar',		'Kalender',			null),
);

$restricted_ext = array('exe', 'jar');

foreach ($filetypes as $index => $row) {
	$thext = $row[1];
	$restricted = in_array(strtolower($thext), $restricted_ext);
	$row[] = $restricted ? 1 : 0;
	$filetypes[$index] = $row;
}

$fields = array(
  'mimetype',
  'extension',
  'groupname',
  'no',
  'icon',
  'restricted_upload'
);

foreach ($filetypes as $index => $row) {
	$values = array();
	foreach ($fields as $valindex => $name) {
		if ($row[$valindex] !== null)
			$values[$name] = SERIA_Base::db()->quote($row[$valindex]);
	}
	SERIA_Base::db()->exec('INSERT INTO `' . SERIA_PREFIX . '_filetypes` ('.implode(', ', array_keys($values)).') VALUES ('.implode(', ', $values).')'); 
}

?>
