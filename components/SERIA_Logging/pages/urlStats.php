<?php
SERIA_Base::pageRequires('admin');
	SERIA_Base::viewMode('system');
	if($_GET["url"]) {
		$url = urldecode($_GET["url"]);
	} else {
		throw new SERIA_Exception("No URL specified");
	}
	$fromDate = $_GET["fromDate"];
	$toDate = $_GET["toDate"];
	if(!$fromDate)
		$fromDate = date('Y-m-d', strtotime('-1 days'));
	if(!$toDate)
		$toDate = date('Y-m-d');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jquery.jqplot.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.categoryAxisRenderer.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.canvasAxisLabelRenderer.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.canvasAxisTickRenderer.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.barRenderer.min.js');
	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.pointLabels.min.js');
//	SERIA_Template::jsInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/js/jqplot.highlighter.min.js');
	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/components/SERIA_Logging/pages/css/jquery.jqplot.min.css');
	SERIA_ScriptLoader::loadScript('jQuery-ui');

/**
*	Overview
*	- Times viewed today and total
*	- Which quality does users end up viewing the video in?
*	- Segment popularty throughout the duration of the video
*	Views per weekday
*	Views per day February 2011
*	Views per day January 2011
*	Views per day December 2010
*	etc
*/
	SERIA_Template::head('jqplot_plotting', "<script type='text/javascript'>
		var statisticsGenerated = false;
		var labelArray;
		function redrawStatistics(fromDate, toDate, namespace)
		{
			statisticsGenerated = true;
			namespace = typeof(namespace) != 'undefined' ? namespace : 'urlstats';
			var maxVal = 0;
			jQuery.getJSON(SERIA_VARS.HTTP_ROOT + '/seria/serialogging/urlStats/api/loadStatistics?url=".$url."&fromDate='+escape(fromDate)+'&toDate='+escape(toDate)+'&namespace='+namespace, function(data) {
				var stats = [];
				var labels= [];
				for (n in data['data']) {
					stats.push(parseFloat(data['data'][n]));

					if(parseInt(data['data'][n])>maxVal)
						maxVal = parseInt(data['data'][n]);
				}
				labelArray = data['labels'];
				var labelCount = data['labels'].length;

				var adder = labelCount / ".(($_GET["res"] ? $_GET["res"] - 1 : 6)).";
				var adder=1;
				for (i = 0; i < (labelCount - 1); i+=adder) {
					var OyvindsMafloi = Math.floor(i);
					element = data['labels'][OyvindsMafloi];
					//labels.push([OyvindsMafloi, data['labels'][OyvindsMafloi]]);
					labels.push(data['labels'][i]);
				}
				//labels.push([(labelCount - 1), data['labels'][(labelCount - 1)]]);
				labels.push(data['labels'][labelCount-1]);

				drawBarChart(stats, 'daychart', 0, (maxVal*1.10), labels);
			});
		}
		function drawLineChart(stats, target, minVal, maxVal, labels)
		{
			$('#'+target).empty();
			$.jqplot.config.enablePlugins = true;
			stats = [1002,4222,7222,8222];
			plot2 = $.jqplot(target, [stats], {
				title:'Hits',
				series: [{
					label: 'Hits', renderer: $.jqplot.BarRenderer
				}],
				axesDefaults: {
					pad: 1.2,
				},
				axes: {
					xaxis: {
						ticks: labels,
					},
					yaxis: {
						min: minVal,
						max: maxVal,
						autoscale:true,
						tickInterval: maxVal/10,
						tickOptions: {
							formatString: '%d'
						}
					}
				},
				seriesDefaults: {
					pointLabels: { show: false },
					fill:true,
					barWidth: 2,
				},

			});
			$('#' + target).append('<div id=\"myToolTip\" style=\"cursor:default;width:110px;position:absolute;display:none;background:#E5DACA;padding:4px;\"></div>');
		}
		function drawBarChart(stats, target, minVal, maxVal, labels)
		{
//stats = [10,20, 30, 50,200,40,1000,200,40,40,40,40,50,10,200,42,42,42222,124,124,22,52,61];
			$('#'+target).empty();
			$.jqplot.config.enablePlugins = true;
			plot2 = $.jqplot(target, [stats], {
				title:'Hits',
				series: [{
					label: 'Hits', renderer: $.jqplot.BarRenderer
				}],
				axes: {
					xaxis: {
						renderer: $.jqplot.CategoryAxisRenderer,
						ticks: labels,
						tickInterval: 20000,
					},
					yaxis: {
						minVal: 0,
					}
				},
				seriesDefaults: {
					renderer: $.jqplot.BarRenderer,
					rendererOptions: {
						barWidth: 20,
					}
				}

			});
			$('#' + target).append('<div id=\"myToolTip\" style=\"cursor:default;width:110px;position:absolute;display:none;background:#E5DACA;padding:4px;\"></div>');
		}

		$(document).ready(function() {
			function myMove (ev, gridpos, datapos, neighbor, plot) {
				if (neighbor == null) {
					$('#myToolTip').fadeOut().empty();
					isShowing = false;
				}
				if (neighbor != null) {
					if ($('#myToolTip').is(':hidden')) {
 						var d = new Date();
						var viewerCount = neighbor['data'].toString().split(',')[1];
						//var myText = labelArray[neighbor['pointIndex']] + '<br>Viewers: ' + viewerCount; // could be any function pulling data from anywhere.
						//$('#myToolTip').html(myText).css({left:gridpos.x, top:gridpos.y}).fadeIn();
					}
				}
			}
			$.jqplot.eventListenerHooks.push(['jqplotMouseMove', myMove]);
			redrawStatistics('".$fromDate."', '".$toDate."', 'articlehits');
		});

		$(function() {
			$.datepicker.formatDate('yyyy-mm-dd');
			$(\"#datepickerFrom\").datepicker({
				onSelect: date_chosen,
			});
			$(\"#datepickerTo\").datepicker({
				onSelect: date_chosen,
			});
		});
		function date_chosen(dateText, inst)
		{
//			dateInfo = dateText.split('/');
			//redrawDayStatistics(dateInfo[2]+'-'+dateInfo[0]+'-'+dateInfo[1]);
//			alert($(\"#datepickerFrom\").val());
			dateInfoFrom = $(\"#datepickerFrom\").val().split('/');
			dateInfoTo = $(\"#datepickerTo\").val().split('/');
			redrawStatistics(dateInfoFrom[2]+'-'+dateInfoFrom[0]+'-'+dateInfoFrom[1]+' 00:00:00',dateInfoTo[2]+'-'+dateInfoTo[0]+'-'+dateInfoTo[1]+' 23:59:59');
		}



	</script>");

$width = isset($_GET["width"]) ? intval($_GET["width"]) : 500;
$height = isset($_GET["height"]) ? intval($_GET["height"]) : 350;


$contents = '
<div style="width:'.$width.'px;height:'.$height.'px;">
	<div style="float:left;z-index:40;" id="dateinput">
<label for="datepickerFrom">Date from: </label>
<input type="text" id="datepickerFrom" value="'.date('m/d/Y').'" />
<label for="datepickerTo">Date to: </label>
<input type="text" id="datepickerTo" value="'.date('m/d/Y').'" />

</div>
		<div id="daychart" style="float:left;width:'.$width.'px;height:'.$height.'px;border:1px solid black"></div>
		<div style="clear:both;"> </div>
	</div>';
	SERIA_Template::parse('<html><head></head><body>'.$contents.'</body></html>');
