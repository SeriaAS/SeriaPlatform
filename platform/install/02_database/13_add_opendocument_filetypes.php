<?php

SERIA_Base::db()->exec('ALTER TABLE `'.SERIA_PREFIX.'_filetypes` ADD COLUMN (en VARCHAR(50))');

$filetypes = array(
	array('Text document',							'application/vnd.oasis.opendocument.text',				'odt', 'Tekstdokument', 'textdocs', null),
	array('Text document used as template',			'application/vnd.oasis.opendocument.text-template',		'ott', 'Tekstmal', 'textdocs', null),
	array('Graphics document (Drawing)',			'application/vnd.oasis.opendocument.graphics',			'odg', 'Tegning', 'images', null),
	array('Drawing document used as template',		'application/vnd.oasis.opendocument.graphics-template',	'otg', 'Mal-tegning', 'images', null),
	array('Presentation document',					'application/vnd.oasis.opendocument.presentation',		'odp', 'Presentasjon', 'presentations', null),
	array('Presentation document used as template',	'application/vnd.oasis.opendocument.presentation-template','otp','Presentasjonsmal', 'presentations', null),
	array('Spreadsheet document',					'application/vnd.oasis.opendocument.spreadsheet',		'ods', 'Regneark', 'spreadsheet', null),
	array('Spreadsheet document used as template',	'application/vnd.oasis.opendocument.spreadsheet-templateæ','ots','Regneark-mal', 'spreadsheet', null),
	array('Chart document',							'application/vnd.oasis.opendocument.chart',				'odc', 'Diagram', 'charts', null),
	array('Chart document used as template',		'application/vnd.oasis.opendocument.chart-template',	'otc', 'Diagram-mal', 'charts', null),
	array('Image document',							'application/vnd.oasis.opendocument.image',				'odi', 'Bilde', 'images', null),
	array('Image document used as template',		'application/vnd.oasis.opendocument.image-template',	'oti', 'Bilde-mal', 'images', null),
	array('Formula document',						'application/vnd.oasis.opendocument.formula',			'odf', 'Formel', 'formula', null),
	array('Formula document used as template',		'application/vnd.oasis.opendocument.formula-template',	'otf', 'Formel-mal', 'formula', null),
	array('Global Text document',					'application/vnd.oasis.opendocument.text-master',		'odm', 'Globalt tekstdokument', 'textdocs', null),
	array('Text document used as template for HTML documents','application/vnd.oasis.opendocument.text-web','oth', 'Tekstdokument HTML-mal', 'textdocs', null)
);

$restricted_ext = array('exe', 'jar');

foreach ($filetypes as $index => $row) {
	$thext = $row[2];
	$restricted = in_array(strtolower($thext), $restricted_ext);
	$row[] = $restricted ? 1 : 0;
	$filetypes[$index] = $row;
}

$fields = array(
  'en',
  'mimetype',
  'extension',
  'no',
  'groupname',
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