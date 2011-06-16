<?php

require_once(dirname(__FILE__).'/../../main.php');

$gui = new SERIA_Gui('character table');

function dumpoct($str)
{
	$g_output = '';
	$len = strlen($str);
	for ($i = 0; $i < $len; $i++) {
		$output = '';
		$val = substr($str, $i, 1);
		$val = ord($val);
		if ($val >= 0) {
			if ($val == 0)
				$output = '0';
			$prefix = '\\';
		} else
			$prefix = '\\-';
		while ($val > 0) {
			$output = ($val % 8) . $output;
			$val = floor($val / 8);
		}
		$g_output .= $prefix . $output;
	}
	return $g_output;
}

$contents = '<table><tr><th>ISO-8859-1 Code</th><th>Octal ISO-8859-1</th><th>Octal UTF-8 code</th><th>Visual</th></tr>';
for ($i = 0; $i < 256; $i++) {
	$str = chr($i);
	$utf8 = utf8_encode($str);
	$contents .= '<tr><td>'.$i.'</td><td>'.dumpoct($str).'</td><td>'.dumpoct($utf8).'</td><td>'.$utf8.'</td></tr>';
}
$contents .= '</table>';

$gui->contents($contents);

echo $gui->output();

?>
