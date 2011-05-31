<?php
	SERIA_Base::pageRequires('admin');
	SERIA_Base::viewMode('admin');
	// DEMODATA DEMODATA DEMODATA
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jquery.jqplot.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.categoryAxisRenderer.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.barRenderer.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.pointLabels.min.js');
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/css/jquery.jqplot.min.css');
	if(isset($GLOBALS['seriamultisite']))
		$domain = $GLOBALS['seriamultisite']['domain'];
	else
		$domain = 'Unable to detect domain';
	$currentDomain = $domain;

	$userHasPrepaidPlan = true;

	if($userHasPrepaidPlan) {
		$max = 3000;
	}

	$byteToGb = 1000000000;
	$bytesToMb = 1000000;
	$useGB = true;

	$counter = new SERIA_Counter('bandwidthusage');

	$usedThisMonth = $counter->get(array('b-Ym:2011-04'));
	$usedGBThisMonth = round((array_pop($usedThisMonth) / ($byteToGb)),2);
	if(!$_GET["q"])
		$queryString = date('Y-m-d');
	else
		$queryString = $_GET["q"];

	$year = date('Y', strtotime($queryString));
	$monthNames = array(_t('January'), _t('February'), _t('March'), _t('April'), _t('May'), _t('June'), _t('July'), _t('August'), _t('September'), _t('October'), _t('November'), _t('December'));
	$i=1;

	foreach($monthNames as $month) {
		$used = $counter->get(array('b-Ym:'.$year.'-'.(strlen($i)==1?('0'.$i):$i)));
		$usedGB = array_pop($used);
		if($usedGB) {
			$months[$i] = array(
				$month.' '.$year =>
					array('used' => $usedGB),
			);
		}
		$i++;
	}

	// DEMOADATA END END END END




	SERIA_Template::head('jqplot_plotting', "<script type='text/javascript'>
		var stats;
		var week_statisticsGenerated = false;
		var day_statisticsGenerated = false;
		var useGB = true;
		function redrawWeekStatistics(week, year, namespace)
		{
			week_statisticsGenerated = true;
			year = typeof(year) != 'undefined' ? year : ".date('Y').";
			namespace = typeof(namespace) != 'undefined' ? namespace : 'bandwidthusage';
			$('#weekTitle').html('Statistics for week ' + week + ' of ' + year);
			var maxVal = 0;
			jQuery.getJSON(SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_Logging/pages/bandwidthUsage/api/loadStatsByWeek.php?week='+week+'&year='+year+'&namespace='+namespace, function(data) {
				var stats = [];
				for (n in data) {
					// Received in bytes, so convert to gigs
					stats.push([n,parseFloat(data[n]/($byteToGb))]);
					if(parseFloat(data[n]/$byteToGb)>maxVal)
						maxVal = parseFloat(data[n]/$byteToGb);
				}
				drawBarChart(stats, 'weekchart', 0, (maxVal*1.10));
			});
		}
		function redrawDayStatistics(date, namespace)
		{
			day_statisticsGenerated = true;
			namespace = typeof(namespace) != 'undefined' ? namespace : 'bandwidthusage';
			var maxVal = 0.5;
			jQuery.getJSON(SERIA_VARS.HTTP_ROOT + '/seria/components/SERIA_Logging/pages/bandwidthUsage/api/loadStatsByDay.php?date='+date+'&namespace='+namespace, function(data) {
				var stats = [];
				for (n in data) {
					stats.push([n, parseFloat(data[n]/($byteToGb))]);

					if(parseFloat((data[n]/$byteToGb))>maxVal)
						maxVal = parseFloat(data[n]/$byteToGb);
				}
				drawLineChart(stats, 'daychart', 0, (maxVal*1.10));
			});
		}

		function drawBarChart(stats, target, minVal, maxVal)
		{
			$('#'+target).empty();
			$.jqplot.config.enablePlugins = true;


			plot1 = $.jqplot(target, [stats], {
				legend: {
					show: true,
					location: 'ne'
				},
				width: 930,
				height: 300,
				seriesDefaults:{
					renderer:$.jqplot.BarRenderer,
					pointLabels: { show: true }
				},
				series: [{
					label: 'GB Transferred'
				}],
				axes: {
					xaxis: {
						renderer: $.jqplot.CategoryAxisRenderer,
					},
					yaxis: {
						min: minVal,
						tickOptions: {
							formatString: '%.2f'
						},
						max: maxVal,
					}
				},
			});

		}

		function drawLineChart(stats, target, minVal, maxVal)
		{
			$('#'+target).empty();
			$.jqplot.config.enablePlugins = true;
			plot2 = $.jqplot(target, [stats], {
				title:'GB Transferred',
				axes: {
					xaxis: {
						renderer: $.jqplot.CategoryAxisRenderer,
					},
					yaxis: {
						min: minVal,
						max: maxVal,
						autoscale:true,
						tickInterval: 0.5,
						tickOptions: {
							formatString: '%.2f'
						}
					}
				},
				seriesDefaults: {
					fill: true,
					fillAndStroke: true,
					fillAlpha:0.5,
					shadow:false,
					pointLabels: { show: true }
}
			});
		}

		$(document).ready(function() {

		});

	</script>");



	if(isset($max) && ($usedGBThisMonth > $max)) {
		$notices.= SERIA_GuiVisualize::notice(_t("You have exceeded the maximum amount of data transferred for your current hosting plan, the exceeding data amount will be charged on your next payment. If you wish to upgrade your hosting plan, please click %here%", array('here' => '<a href="#">'._t("here").'</a>')));
	}

?><s:gui title="{'Bandwidth usage'|_t}">
	<h1 class="legend">{{"Bandwidth usage"|_t}}</h1>
	{{$notices}}
	<fieldset id="this_month_usage" style="padding:10px;">
	<h2>{{"This month: "|_t}}</h2>
		<p style="float:left;"><?php echo $currentDomain; ?></p>
		<div style="width:300px;float:right;">
			<div style="width:300px;float:right;">
				<?php
					if(isset($max)) {
						echo SERIA_GuiVisualize::progressbar($usedGBThisMonth/$max);
					} else {
						echo SERIA_GuiVisualize::progressbar($usedGBThisMonth);
					}
				?>
			</div>
			<div style="float:right;width:300px;text-align:right;">
				<p><?php 
					echo $usedGBThisMonth;

					if(isset($max))
						echo " / ".$max." ";
				?> GB</p>
			</div>
			<div style="float:right;width:300px;text-align:right;">
				<p><?php if(isset($max)) echo round((($usedGBThisMonth/$max)*100),2)."%"; ?></p>
			</div>
			<div style="clear:both;">

			</div>
		</div>
	</fieldset>

	<script type="text/javascript">
		$(document).ready(function() {
			$("#statistics_tab").tabs({
				select: tab_select_function
			});
			$.datepicker.formatDate('yyyy-mm-dd');
			$("#datepicker").datepicker({
				onSelect: date_chosen
			});
		});
		function date_chosen(dateText, inst)
		{
			dateInfo = dateText.split('/');
			redrawDayStatistics(dateInfo[2]+'-'+dateInfo[0]+'-'+dateInfo[1]);
		}
		function tab_select_function(tab, ui)
		{
			if(ui.index == 1 && !week_statisticsGenerated)
				redrawWeekStatistics(document.getElementById('weekselect').value);
			if(ui.index == 2 && !day_statisticsGenerated) {
				redrawDayStatistics($("#datepicker").val());
			}
		}
	</script>

	<div id="statistics_tab">
		<ul>
			<li><a href="#Monthly"><span>Monthly</span></a></li>
			<li><a href="#Weekly"><span>Weekly spread</span></a></li>
			<li><a href="#Daily"><span>24-Hour spread</span></a></li>
		</ul>
		<div id="Monthly">
<?php
	if(!isset($max)) {
		$maxProgress = 0;
		$alertUser = false;
		foreach($months as $month => $usage) {
			$usedGB = $usage[0][0][0];
			if($usedGB>$maxProgress)
				$maxProgress = $usedGB;
		}
	}
$months = array_reverse($months, true);
	foreach($months as $num => $month) {
		$monthName = key($month);
		$usage = array_pop($month);
		$usedGB = round($usage['used']/$byteToGb,2);
		echo "<div style='float:left;width:100%;height:25px;'>
			<div style='width:300px;float:left;line-height:25px;height:25px;'><strong>".$monthName."</strong></div>
			<div style='width:300px;float:right;'>".(isset($max) ? SERIA_GuiVisualize::progressbar($usedGB/$max) : SERIA_GuiVisualize::progressbar($usedGB/$maxProgress))."</div>
			<div style='width:200px;float:right;text-align:right;padding-right:20px;height:25px;line-height:25px;'><strong>".$usedGB.(isset($max) ? "/".$max : '')." GB</strong></div>
			<div style='clear:both;'></div>
		</div>";
	}
?>
			<div style="clear:both;"></div>
		</div>
		<div id="Weekly" style="width:97%;" show="alert('hei');">
			<div id="weekselector" style="width:100%;height:50px;">
				<label for="yearselect">Year: </label><select id="yearselect" onchange="redrawWeekStatistics(document.getElementById('weekselect').value, document.getElementById('yearselect').value)">
<?php
	for($i=0;$i<5;$i++)
		echo '<option>'.date('Y', strtotime('-'.$i.' year')).'</option>';
?>
				</select>
				<label for="weekselect">Week:</label>
				<select id="weekselect" onchange="redrawWeekStatistics(document.getElementById('weekselect').value, document.getElementById('yearselect').value)">
<?php
	for($i=1;$i<=52;$i++) {
		echo '<option '.(date('W')==$i ? ' selected' : '').'>'.$i.'</option>';
	}
?>
				</select>
			</div>
			<h2 id="weekTitle"></h2>
			<div id="weekchart" style="width:933px;height:400px"> </div>
		</div>
		<div id="Daily">
			<label for="datepicker">Select date: </label><input type="text" id="datepicker" value="<?php echo date("Y-m-d");?>" />
			<div id="daychart" style="width:933px;height:400px"> </div>
			<span class="info">(UTC Time)</span>
		</div>
	</div>

</s:gui>


<?php

?>
