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
            <width>320</width>
            <height>240</height>
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
        <format>H.264</format>
        <datarate>650;</datarate>
        <outputsize>320x240;</outputsize>
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
        <datarate>128</datarate>
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

return '<?xml version="1.0" encoding="UTF-8"?>
<flashmedialiveencoder_profile>
    <preset>
        <name>Custom</name>
        <description></description>
    </preset>
    <capture>
        <video>
        <device></device>
        <crossbar_input>0</crossbar_input>
        <frame_rate>15.00</frame_rate>
        <size>
            <width>320</width>
            <height>240</height>
        </size>
        </video>
        <audio>
        <device></device>
        <crossbar_input>0</crossbar_input>
        <sample_rate>22050</sample_rate>
        <channels>1</channels>
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
        <datarate>200;</datarate>
        <outputsize>320x240;</outputsize>
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
        <datarate>48</datarate>
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
        <url>rtmp://localhost/live</url>
        <backup_url></backup_url>
        <stream>livestream</stream>
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
        <directory>C:\Users\Joakim\Videos</directory>
    </log>
</flashmedialiveencoder_profile>';

		$quality = unserialize($article->get("quality"));
		return '<?xml version="1.0" encoding="UTF-8"?><flashmedialiveencoder_profile>
    <preset>
        <name>'.$article->get("title").'</name>
        <description>'.$article->get("description").'</description>
    </preset>
    <capture>
        <video>
        <device></device>
        <crossbar_input>0</crossbar_input>
        <frame_rate>'.$quality['preview_framerate'].'</frame_rate>
        <size>
            <width>'.$quality['preview_width'].'</width>
            <height>'.$quality['preview_height'].'</height>
        </size>
        </video>
	<audio>
        <device></device>
        <crossbar_input>0</crossbar_input>
        <sample_rate>22050</sample_rate>
        <channels>1</channels>
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
        <format>'.$quality['encode_format'].'</format>
        <datarate>'.$quality['encode_datarate'].';</datarate>
        <outputsize>'.$quality['encode_width'].'x'.$quality['encode_height'].';</outputsize>
        <advanced>
            <keyframe_frequency>3 Seconds</keyframe_frequency>
            <quality>Good Quality - Good Framerate</quality>
            <noise_reduction>None</noise_reduction>
            <datarate_window>Medium</datarate_window>
            <cpu_usage>Dedicated</cpu_usage>
        </advanced>
        <autoadjust>
            <enable>'.$quality['auto_adjust'].'</enable>
            <maxbuffersize>'.$quality['auto_adjust_maxbuffersize'].'</maxbuffersize>
            <dropframes>
            <enable>'.$quality['auto_adjust_dropframes'].'</enable>
            </dropframes>
            <degradequality>
            <enable>'.$quality['auto_adjust_degradequality'].'</enable>
            <minvideobitrate></minvideobitrate>
            <preservepfq>false</preservepfq>
            </degradequality>
        </autoadjust>
        </video>
	<audio>
        <format>MP3</format>
        <datarate>48</datarate>
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
        <backup_url>'.$article->get("fms_backup").'</backup_url>
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
        <path>serialive_'.$article->get("id").'.flv</path>
        </file>
    </output>
    <metadata>
        <entry>
        <key>author</key>
        <value>'.$article->get("author_name").'</value>
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
        <value>'.$article->get("title").'</value>
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
		</flashmedialiveencoder_profile>';
	}

	if($_POST) {
		if(!isset($_POST["localHours"]) || !isset($_POST["localMinutes"]) || !isset($_POST["localSeconds"]))
			throw new SERIA_Exception("Did not receive any timecode information, do you have javascript enabled?");
		$article = SERIA_Article::createObjectFromId($_POST["articleId"]);
		if(!LiveAPI::verifyKey($_POST["articleId"], $_POST["hash"]))
			throw new SERIA_Exception('Unable to verify hashkey, please try again with the correct url');

		$timestamp = gmmktime($_POST["localHours"], $_POST["localMinutes"], $_POST["localSeconds"], $_POST["localMonth"], $_POST["localDate"], $_POST["localYear"]);

		LiveAPI::setEncoderDelay(intval($_POST["articleId"]), $timestamp-gmmktime());

		header("Content-Type: text/xml");
		header("Content-Type: application/force-download");
		header("Content-Disposition: attachment; filename=fme_config_".$_POST["articleId"].".xml");
		header("Content-Type: application/octet-stream");
		header("Content-Type: application/download");
		header("Content-Description: File Transfer");

		$html.=generateXML($article);

		SERIA_Template::disable();

		echo $html;
	} else {

		SERIA_Template::jsInclude('jQuery');

		$template = '<html><head>
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

		$template.= '<body><h1>'.("Generer konfigurasjonsfil for din Flash Media Encoder").'</h1>';

		$template.= '<p style="color:red;">'.("Obs obs: Det er veldig viktig at datamaskinen som benytter seg av konfigurasjonsfilen ogs&aring; er datamaskinen som laster denne filen ned!").'</p>';

		$template.= '<form action="'.SERIA_HTTP_ROOT.'/seria/apps/live/api/generateFMEConfigXML.php" method="post">
					<table>
						<tr>
							<td>
								<input type="hidden" id="localYear" name="localYear" value="0" />
								<input type="hidden" id="localMonth" name="localMonth" value="0" />
								<input type="hidden" id="localDate" name="localDate" value="0" />
								<input type="hidden" id="localHours" name="localHours" value="0" />
								<input type="hidden" id="localMinutes" name="localMinutes" value="0" />
								<input type="hidden" id="localSeconds" name="localSeconds" value="0" />
								<input type="hidden" name="articleId" value="'.$_GET["articleId"].'" />
								<input type="hidden" name="hash" value="'.$_GET["hash"].'" />
								<input type="submit" value="Last ned XML profil" id="submitButton" onmousedown="setTime();" />
							</td>
						</tr>
					</table>
				</form>';

		$template.= '</body></html>';

		SERIA_Template::parse($template);
	}


