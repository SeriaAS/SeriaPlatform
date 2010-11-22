<?php

require_once(dirname(__FILE__) . "/../../../../main.php");

echo "<h1>DateLocale date format test</h1>";

$isoDate = date('Y-m-d');
echo "ISO: " . $isoDate . "<br>\n";
$tsC = strtotime($isoDate);
echo "Correct timestamp: " . $tsC . "<br>\n";
$dateloc = SERIA_Locale::getLocale();
$loc = $dateloc->timeToString($tsC);
echo "Local date: " . $loc . "<br>\n";
$ts = $dateloc->stringToTime($loc);
echo "Back to timestamp: " . $ts . "<br>\n";
if ($ts != $tsC)
	echo "WARNING: There is a bug in conversion (timestamp values do not match)<br>\n";


echo "<h1>DateLocale date-time format test</h1>";

$isoDate = date('Y-m-d H:i:s');
echo "ISO: " . $isoDate . "<br>\n";
$tsC = strtotime($isoDate);
echo "Correct timestamp: " . $tsC . "<br>\n";
$dateloc = SERIA_Locale::getLocale();
$loc = $dateloc->timeToString($tsC, "datetime");
echo "Local date: " . $loc . "<br>\n";
$ts = $dateloc->stringToTime($loc, "datetime");
echo "Back to timestamp: " . $ts . "<br>\n";
if ($ts != $tsC)
	echo "WARNING: There is a bug in conversion (timestamp values do not match)<br>\n";

echo "<h1>DateLocale time format test</h1>";

$loc = $dateloc->timeToString($tsC, "time");
echo "Local time: " . $loc . "<br>\n";
if (($error = SERIA_IsInvalid::localTime($loc, true)) !== false)
	echo 'Error: ' . $error . "<br>\n";
$ts = $dateloc->stringToTime($loc, 'time', $tsC);
echo "Back to timestamp: " . $ts . "<br>\n";
if ($ts != $tsC)
	echo "WARNING: There is a bug in conversion (timestamp values do not match)<br>\n";

echo "<h1>DateLocal nosec test</h1>";

$loc = $dateloc->timeToString($tsC, 'timenosec');
echo 'Local time: ' . $loc . "<br>\n";
$ts = $dateloc->stringToTime($loc, 'timenosec', $tsC);
echo 'Back to timestamp: '.$ts."<br>\n";
$tsStripSeconds = mktime(date('H', $tsC), date('i', $tsC), 0, date('m', $tsC), date('d', $tsC), date('Y', $tsC));
if ($tsStripSeconds != $ts) {
	echo "WARNING: There is a bug in conversion (timestamp values do not match)<br>\n";
	$parse = $dateloc->emu_strptime($loc, '%H:%M');
	print_r($parse);
}

?>
