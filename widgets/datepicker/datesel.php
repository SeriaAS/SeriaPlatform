<?php


	require_once(dirname(__FILE__)."/../../main.php");

	SERIA_Template::headEnd("projectsHEAD", $css);

	$gui = new SERIA_Gui(_t("Calendar"));

	$gui->exitButton("Back", "history.go(-1)");

	/*
	 * Code for displaying day tables..
	 */
	function calendarTableContents($year, $month, $day, &$output=false)
	{
		$monthStart = mktime(1, 0, 0, $month, 1, $year);
		$monthEnd = mktime(1, 0, 0, $month + 1, 0, $year);
		$monthStartWDay = date('N', $monthStart);
		$monthEndDay = date('d', $monthEnd);

		if ($day > $monthEndDay)
			$day = $monthEndDay;

		$mdayArr = array();
		$arrStartIndex = $monthStartWDay - 1;
		$arrSize = $arrStartIndex + $monthEndDay;
		for ($i = $arrStartIndex; $i < $arrSize; $i++)
			$mdayArr[$i] = true;
		
		$contents = "\t\t<thead>\n";
		$contents .= "\t\t\t<tr><th>"._t('M', array(), '*Monday')."</th><th>"._t('T', array(), '*Tuesday')."</th><th>"._t('W', array(), '*Wednesday')."</th><th>"._t('T', array(), '*Thursday')."</th><th>"._t('F', array(), '*Friday')."</th><th>"._t('S', array(), '*Saturday')."</th><th>"._t('S', array(), '*Sunday')."</th></tr>\n";
		$contents .= "\t\t</thead>\n";
		$contents .= "\t\t<tbody>\n";
		for ($i = 0; $i < $arrSize || ($i % 7) != 0; $i++) {
			if (($i % 7) == 0)
				$contents .= "\t\t\t<tr>";
			$mday = 1 + $i - $arrStartIndex;
			if (isset($mdayArr[$i])) {
				$contents .= "<td id='".$mday."' onclick='dayClicked(".$mday.");' onmouseover='if(!this._active) this.style.backgroundColor=\"#ccc\"' onmouseout='if(!this._active) this.style.backgroundColor=\"\"'>";
				$contents .= $mday;
			} else
				$contents .= "<td>";
			$contents .= "</td>";
			if ((($i + 1) % 7) == 0)
				$contents .= "</tr>\n";
		}
		$contents .= "\t\t</tbody>\n";
		if ($output !== false) {
			$output["year"] = $year;
			$output["month"] = $month;
			$output["day"] = $day;
		}
		return $contents;
	}
	if (isset($_GET['datetime'])) {
		$year = isset($_GET['year'])?$_GET['year']:'';
		$month = isset($_GET['month'])?$_GET['month']:'';
		$day = isset($_GET['day'])?$_GET['day']:'';
		$hour = isset($_GET['hour'])?$_GET['hour']:'';
		$min = isset($_GET['min'])?$_GET['min']:'';
		$sec = isset($_GET['sec'])?$_GET['sec']:'';
		if ($year === '')
			$year = false;
		if ($month === '')
			$month = false;
		if ($day === '')
			$day = false;
		if ($hour === '')
			$hour = false;
		if ($min === '')
			$min = false;
		if ($sec === '')
			$sec = false;
		if ($hour === false)
			$ts = mktime();
		else if ($min === false)
			$ts = mktime($hour);
		else if ($sec === false)
			$ts = mktime($hour, $min);
		else if ($month === false)
			$ts = mktime($hour, $min, $sec);
		else if ($day === false)
			$ts = mktime($hour, $min, $sec, $month);
		else if ($year === false)
			$ts = mktime($hour, $min, $sec, $month, $day);
		else
			$ts = mktime($hour, $min, $sec, $month, $day, $year);
		$output['year'] = date('Y', $ts);
		$output['month'] = date('m', $ts);
		$output['day'] = date('d', $ts);
		$output['hour'] = date('H', $ts);
		$output['min'] = date('i', $ts);
		$output['sec'] = date('s', $ts);
		$dateloc = SERIA_Locale::getLocale();
		$output['date'] = $dateloc->timeToString($ts);
		$output['time'] = $dateloc->timeToString($ts, 'time');
		$output['timenosec'] = $dateloc->timeToString($ts, 'timenosec');
		$output['datetime'] = $dateloc->timeToString($ts, 'datetime');
		$output['datetimenosec'] = $dateloc->timeToString($ts, 'datetimenosec');
		$output['request'] = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $min . ':' . $sec;
		SERIA_Lib::publishJSON($output);
		die();
	}
	if (isset($_GET["jsreq_year"]) && isset($_GET["jsreq_month"]) && isset($_GET["jsreq_day"])) {
		SERIA_Template::disable();
		SERIA_Base::pageRequires("javascript");
		$output = array();
		if (isset($_GET["jsreq_code"])) {
			$code = calendarTableContents($_GET["jsreq_year"], $_GET["jsreq_month"], $_GET["jsreq_day"], $output);
			$output["code"] = $code;
		}
		if (isset($_GET["jsreq_datefmt"])) {
			$output["date"] = SERIA_Locale::getLocale()->dateToStringMDY($_GET["jsreq_month"], $_GET["jsreq_day"], $_GET["jsreq_year"]);
		}
		SERIA_Lib::publishJSON($output);
		die();
	}
	
	$contents = "";

/* BEGIN CSS CODE */
$css = "

<style type='text/css'>
<!--
-->
</style>

";
/* END CSS CODE */
/* BEGIN SCRIPT CODE */
$script = "
<script type='text/javascript'>

var textBoxId = self.textBoxId;
if (!textBoxId)
	textBoxId = top.opener.SERIA.DatepickerWidgetDataStorage.textBoxId;

var prevday = false;
var fgcol;

var selDay;
var selMonth = '00';
var selYear = '0000';

function formChanged()
{
	SERIA.Lib.AJSON('', {'jsreq_year': selYear, 'jsreq_month': selMonth, 'jsreq_day': selDay, 'jsreq_code': 'yes'}, function (data) {
		selYear = data.year;
		selMonth = data.month;
		selDay = data.day;
		$('#caldisp').html(data.code);
	});
}
function monthChanged(obj)
{
	selMonth = obj.value;
	formChanged();
}
function yearChanged(obj)
{
	selYear = obj.value;
	formChanged();
}
function dayClicked(mday)
{
	var dayelem = document.getElementById(mday);

	dayelem._active = true;

	selDay = mday;
	if (prevday) {
		prevday._active = false;
		prevday.style.backgroundColor = '#FFFFFF';
		prevday.style.color = fgcol;
	}
	fgcol = dayelem.style.color;
	dayelem.style.backgroundColor = '#444444';
	dayelem.style.color = '#FFFFFF';
	prevday = dayelem;
}
function passValue()
{
	SERIA.Lib.AJSON('', {'jsreq_year': selYear, 'jsreq_month': selMonth, 'jsreq_day': selDay, 'jsreq_datefmt': 'yes'}, function (data) {
		top.opener.calendarWillCallMe(textBoxId, data.date);
		self.close();
	});
}

</script>
";
/* END SCRIPT CODE */

	$contents .= $script;

	$months = array();
	$months[1] = _t("January");
	$months[2] = _t("February");
	$months[3] = _t("March");
	$months[4] = _t("April");
	$months[5] = _t("May");
	$months[6] = _t("June");
	$months[7] = _t("July");
	$months[8] = _t("August");
	$months[9] = _t("September");
	$months[10] = _t("October");
	$months[11] = _t("November");
	$months[12] = _t("December");

	if (isset($_GET["month"])) {
		$month = intval($_GET["month"]);
		if ($month < 1 || $month > 12)
			$month = 1;
	} else
		$month = date('m');
	if (isset($_GET["year"]))
		$year = intval($_GET["year"]);
	else
		$year = date('Y');
	if (isset($_GET["day"])) {
		$day = intval($_GET["day"]);
		if ($day < 1)
			$day = 1;
	} else
		$day = date('d');
	if (isset($_GET["value"]) && !empty($_GET["value"])) {
		$val = $_GET["value"];
		$dateloc = SERIA_Locale::getLocale();
		$tm = $dateloc->stringToTime($val);
		$year = date('Y', $tm);
		$month = date('m', $tm);
		$day = date('d', $tm);
	}
	$contents .= "<form>\n";
	$contents .= "\t<select name='month' onChange='monthChanged(this);'>\n";
	for ($i = 1; $i <= 12; $i++) {
		$selected = ($i == $month) ? "selected" : "";
		$contents .= "\t\t<option value='".$i."' ".$selected.">" . $months[$i] . "</option>\n";
	}
	$contents .= "\t</select>\n";

	$contents .= "\t<select name='year' onChange='yearChanged(this);'>\n";
	$refyear = intval(date('Y'));
	$ym10 = $refyear - 20;
	$yp10 = $refyear + 20;
	if ($year < $ym10 || $year > $yp10)
		$year = intval(date('Y'));
	for ($i = $ym10; $i <= $yp10; $i++) {
		$selected = ($i == $year) ? "selected" : "";
		$contents .= "\t\t<option value='".$i."' ".$selected.">".$i."</option>\n";
	}
	$contents .= "\t</select>\n";


	$contents .= "\t<table id='caldisp' class='grid' style='margin-top: 5px;height: 140px;width: 100%; border-collapse: collapse; cursor: pointer;'>\n";

	$contents .= calendarTableContents($year, $month, $day);

	$contents .= "\t</table>\n";

	$contents
		.= "<button type='button' onclick='passValue();'>" . _t("OK") . "</button>\n"
		.  "<button type='button' onclick='self.close();'>" . _t("Cancel") . "</button>\n";
	

	$contents .= "</form>\n";

$script = "
<script type='text/javascript'>
	selMonth = ".$month.";
	selYear = ".$year.";
	dayClicked(".$day.");
</script>
";
/* END SCRIPT CODE */

	$contents .= $script;

	$gui->contents($contents);

	echo $gui->output(true/*isPopup*/);

?>
