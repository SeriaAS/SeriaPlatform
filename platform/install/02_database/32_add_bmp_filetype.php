<?php 

$filetypes = array(
	array('Windows OS/2 Bitmap Graphics', 'image/bmp',	'bmp', 'Windows OS/2 Bitmap Grafikk', 'images', null)
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