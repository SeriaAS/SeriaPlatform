<?php
	require_once(dirname(__FILE__).'/../../../main.php');
	/**
	 *	$_GET["articleId"] is the article id in question
	 *	$_GET["localTime"] is the local timestamp of the computer running the flash media encoder program
	 *
	 *	We use these params to generate a delay compared to server time. We then use that delay to synchronize
	 *	the foilmanager's timestamp to the encoder's timestamp. The videoframes will then be encoded with TS=x
	 *	which we then can use to fetch a foil positioned at TS=x in our event list. (listening to onFI in flash client)
	 *
	 **/

	function generateXML($article)
	{

return '<flashmedialiveencoder_profile>
<preset>
<name>Custom</name>
<description></description>
</preset>
<capture>
<video>
<device></device>
<crossbar_input>0</crossbar_input>
<frame_rate>25.00</frame_rate>
<size>
<width>640</width>
<height>480</height>
</size>
</video>
<audio>
<device></device>
<crossbar_input>0</crossbar_input>
<sample_rate>44100</sample_rate>
<channels>2</channels>
<input_volume>75</input_volume>
</audio>
</capture>
<process>
<video>
<preserve_aspect></preserve_aspect>
</video>
</process>
<encode>
<video>
<format>VP6</format>
<datarate>544;</datarate>
<outputsize>640x480;</outputsize>
<advanced>
<keyframe_frequency>5 Seconds</keyframe_frequency>
<quality>Good Quality - Good Framerate</quality>
<noise_reduction>None</noise_reduction>
<datarate_window>Medium</datarate_window>
<cpu_usage>Dedicated</cpu_usage>
</advanced>
<autoadjust>
<enable>false</enable>
<maxbuffersize>1</maxbuffersize>
<dropframes>
<enable>false</enable>
</dropframes>
<degradequality>
<enable>false</enable>
<minvideobitrate></minvideobitrate>
<preservepfq>false</preservepfq>
</degradequality>
</autoadjust>
</video>
<audio>
<format>MP3</format>
<datarate>96</datarate>
</audio>
</encode>
<restartinterval>
<days></days>
<hours></hours>
<minutes></minutes>
</restartinterval>
<reconnectinterval>
<attempts></attempts>
<interval></interval>
</reconnectinterval>
<output>
<rtmp>
<url>rtmp://rtmpin1.seriacdn.com/publish</url>
<backup_url></backup_url>
<stream>hegnar1_3828</stream>
</rtmp>
</output>
<metadata>
<entry>
<key>author</key>
<value></value>
</entry>
<entry>
<key>copyright</key>
<value></value>
</entry>
<entry>
<key>description</key>
<value></value>
</entry>
<entry>
<key>keywords</key>
<value></value>
</entry>
<entry>
<key>rating</key>
<value></value>
</entry>
<entry>
<key>title</key>
<value></value>
</entry>
</metadata>
<preview>
<video>
<input>
<zoom>100%</zoom>
</input>
<output>
<zoom>100%</zoom>
</output>
</video>
<audio></audio>
</preview>
<log>
<level>100</level>
<directory></directory>
</log>
</flashmedialiveencoder_profile>
';

return '<?xml version="1.0" encoding="UTF-8"?>
<flashmedialiveencoder_profile>
<preset>
<name>High Bandwidth (800 Kbps) - VP6</name>
<description></description>
</preset>
<capture>
<video>
<device>VHScrCap</device>
<crossbar_input>0</crossbar_input>
<frame_rate>15.00</frame_rate>
<size>
<width>720</width>
<height>576</height>
</size>
</video>
<audio>
<device></device>
<crossbar_input>0</crossbar_input>
<sample_rate>44100</sample_rate>
<channels>2</channels>
<input_volume>75</input_volume>
</audio>
<timecode>
<frame_rate>25</frame_rate>
<systemtimecode>true</systemtimecode>
<devicetimecode>
<enable>false</enable>
<vertical_line_no>16</vertical_line_no>
<burn>false</burn>
<row>Bottom</row>
<column>Left</column>
</devicetimecode>
</timecode>
</capture>
<process>
<video>
<preserve_aspect></preserve_aspect>
</video>
</process>
<encode>
<video>
<format>VP6</format>
<datarate>554;</datarate>
<outputsize>640x360;</outputsize>
<advanced>
<keyframe_frequency>5 Seconds</keyframe_frequency>
<quality>Good Quality - Good Framerate</quality>
	<profile>Baseline</profile>
	<level>3.0</level>
<noise_reduction>None</noise_reduction>
<datarate_window>Medium</datarate_window>
<cpu_usage>Dedicated</cpu_usage>
</advanced>
<autoadjust>
<enable>false</enable>
<maxbuffersize>1</maxbuffersize>
<dropframes>
<enable>false</enable>
</dropframes>
<degradequality>
<enable>false</enable>
<minvideobitrate></minvideobitrate>
<preservepfq>false</preservepfq>
</degradequality>
</autoadjust>
</video>
<audio>
<format>MP3</format>
<datarate>96</datarate>
</audio>
</encode>
<restartinterval>
<days></days>
<hours></hours>
<minutes></minutes>
</restartinterval>
<reconnectinterval>
<attempts></attempts>
<interval></interval>
</reconnectinterval>
<output>
<rtmp>
<url>rtmp://'.$article->get("fms").'/'.$article->get("application_name").'</url>
<backup_url></backup_url>
<stream>'.$article->get("publish_point").'</stream>
</rtmp>
<file>
<limitbysize>
<enable>false</enable>
<size>10</size>
</limitbysize>
<limitbyduration>
<enable>false</enable>
<hours>1</hours>
<minutes>0</minutes>
</limitbyduration>
<path>sample.flv</path>
</file>
</output>
<metadata></metadata>
<preview>
<video>
<input>
<zoom>100%</zoom>
</input>
<output>
<zoom>100%</zoom>
</output>
</video>
<audio></audio>
</preview>
<log>
<level>100</level>
<directory></directory>
</log>
</flashmedialiveencoder_profile>
';
	}

	if($_POST) {
		if(!isset($_POST["localHours"]) || !isset($_POST["localMinutes"]) || !isset($_POST["localSeconds"]))
			throw new SERIA_Exception("Did not receive any timecode information, do you have javascript enabled?");
		$article = SERIA_Article::createObjectFromId($_POST["articleId"]);
		if(!LiveAPI::verifyKey($_POST["articleId"], $_POST["hash"]))
			throw new SERIA_Exception('Unable to verify hashkey, please try again with the correct url');

		$timestamp = gmmktime($_POST["localHours"], $_POST["localMinutes"], $_POST["localSeconds"], $_POST["localMonth"], $_POST["localDate"], $_POST["localYear"]);

		LiveAPI::setEncoderDelay(intval($_POST["articleId"]), $timestamp-gmmktime());

//		header("Content-Type: text/xml");
//		header("Content-Type: application/force-download");
//		header("Content-Disposition: attachment; filename=fme_config_".$_POST["articleId"].".xml");
//		header("Content-Type: application/octet-stream");
//		header("Content-Type: application/download");
//		header("Content-Description: File Transfer");

//		$html.=generateXML($article);

//		SERIA_Template::disable();
		$template = '<html style="height:100%;background-color:#3d3d3d;"><head>
				<title>Synchronization</title>
				</head>';

		$template.= '<body style="margin:0px;padding:0px;"><div style="awidth:100%;aheight:100%;">';

		$template.= '<div style="width:500px;height:370px;margin:auto;padding:10px;margin-top:150px;background-color:#FFF;border:2px solid #C3C3C3;-webkit-border-radius: 6px;-moz-border-radius: 6px;border-radius: 6px;">';

		$template.= '<div style="text-align:left;width:100%;"><h1>'._t("This computer is now synchronized with Seria Media Servers").'</h1></div>';

		$template.= '<table><tr><td><img style="width:60px;height:53px;" src="'.SERIA_HTTP_ROOT.'/templates/images/warning_finished.png" /></td><td><p style="color:red;">'._t("The following publish points will only have synchronized slides if used from this computer!").'</p></td></tr></table>';

		$template.= '<table><tr><td><img style="width:60px;height:53px;" src="'.SERIA_HTTP_ROOT.'/templates/images/warning_finished.png" /></td><td><p style="color:red;">'._t("You must also remember to activate the \"Timecode\" function in Flash Media Encoder!").'</p></td></tr></table>';

		$template.= '<table style="margin-top:10px;">
				<tr>
					<td style="width:200px;">'._t("Publish point").':</td><td style="font-weight:bold;">'.$article->get("publish_point").'</td>
				</tr>
				<tr>
					<td>'._t("Flash Media Server").': </td><td style="font-weight:bold;">rtmp://'.$article->get("fms").'/'.$article->get("application_name").'</td>
				</tr>
				</table>';

		$template.= '</div></div>';

		$template.= '</body></html>';

		SERIA_Template::parse($template);

		//echo $html;
	} else {

		if(!$_GET["articleId"])
			throw new SERIA_Exception('Invalid Article ID');

		SERIA_Template::jsInclude('jQuery');

		$template = '<html style="height:100%;background-color:#3d3d3d"><head>
					<script type="text/javascript" language="javascript">

					function setTime()
					{
						var date = new Date();
//						var dateServer = Date.UTC(date.getFullYear(), parseInt(date.getMonth()+1), date.getDate(), date.getHours(), date.getMinutes(), date.getSeconds());
//						var dateServer = new Date("2010", "11", "8", "18", "51", "00");
//						alert((dateServer.getTime()-date.getTime())/1000);
						//alert((dateServer/1000)-(date.getTime()/1000));
						//$("#localTime").val(parseInt(dateServer.getTime()/1000));
						$("#localYear").val(parseInt(date.getFullYear()));
						$("#localMonth").val(parseInt(date.getMonth())+1); // +1 since its from 0-11
						$("#localDate").val(parseInt(date.getDate()));
						$("#localHours").val(parseInt(date.getHours()));
						$("#localMinutes").val(parseInt(date.getMinutes()));
						$("#localSeconds").val(parseInt(date.getSeconds()));

					}

					setInterval(setTime, 200);
					</script>
				</head>';

		$template.= '<body style="margin:0px;padding:0px;"><div style="awidth:100%;aheight:100%;">';

		$template.= '<div style="width:500px;height:280px;margin:auto;padding:10px;padding-left:20px;margin-top:150px;background-color:#FFF;border:2px solid #C3C3C3;-webkit-border-radius: 6px;-moz-border-radius: 6px;border-radius: 6px;">';

		$template.= '<div style="text-align:left;width:100%;"><h1>'._t("Synchronize with server").'</h1></div>';

		$template.= '<table><tr><td><img style="width:110px;height:90px;" src="'.SERIA_HTTP_ROOT.'/templates/images/warning_finished.png" /></td><td><p style="color:red;">'._t("If the webcast has presentation slides it is assosciated with, they will not be synchronized unless this is the computer running the Flash Media Encoder program. Clicking the button below will synchronize this computer with the mediaserver distributing your webcast. If this is not the computer running Flash Media Encoder, please visit this url on that computer to obtain synchronization.").'</p></td></tr></table>';

		$template.= '<br>
			<form action="'.SERIA_HTTP_ROOT.'/seria/apps/live/api/generateFMEConfigXML.php?joakim=1" method="post">
					<table style="width:100%;">
						<tr>
							<td style="width:100%;text-align:right;">
								<input type="hidden" id="localYear" name="localYear" value="0" />
								<input type="hidden" id="localMonth" name="localMonth" value="0" />
								<input type="hidden" id="localDate" name="localDate" value="0" />
								<input type="hidden" id="localHours" name="localHours" value="0" />
								<input type="hidden" id="localMinutes" name="localMinutes" value="0" />
								<input type="hidden" id="localSeconds" name="localSeconds" value="0" />
								<input type="hidden" name="articleId" value="'.$_GET["articleId"].'" />
								<input type="hidden" name="hash" value="'.$_GET["hash"].'" />
								<input type="submit" value="Synchronize" id="submitButton" onmousedown="setTime();" />
							</td>
						</tr>
					</table>
				</form>';

		$template.= '</div></div>';

		$template.= '</body></html>';

		SERIA_Template::parse($template);
	}


