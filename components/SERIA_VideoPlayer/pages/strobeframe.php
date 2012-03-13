<?php

	/**
	**
	**	Strobe Video Player
	**
	**	Requires the following parameters
	**
	**	$options = array(
	**		'src' => 'sourceToPlay',
	**	);
	**
	**
	**
	**
	*/

	if(isset($_GET['admin']) && SERIA_Base::isLoggedIn())
		SERIA_Base::viewMode('system');

	if(!is_numeric($_GET["objectKey"]))
		throw new SERIA_Exception("Invalid object key, must be integer");

	try {
		$media = SERIA_NamedObjects::getInstanceByPublicId($_GET["objectKey"], 'SERIA_IVideoData');
	} catch(SERIA_Exception $e) {
		SERIA_Base::viewMode('admin');
		try {
			$media = SERIA_NamedObjects::getInstanceByPublicId($_GET['objectKey'], 'SERIA_IVideoData');
			echo "Not publihsed";
			return;
		} catch(SERIA_Exception $e) {
			echo "Not found";
			return;
		}
	}

	$videoData = $media->getVideoData();

	$videoDataSources = $videoData['sources'];



?><!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
	<title>SERIA_VideoPlayer</title>
	<style type="text/css">
		body {
			font: 12px "Trebuchet MS", sans-serif, Arial;
			background-color: #000000;
			color: #FFFFFF;
			height: 100%;
			width: 100%;
			margin: 0;
			overflow: hidden;
		}
	</style>


	<link type="text/css" href="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/jquery.strobemediaplayback.css" rel="stylesheet" />
	<script type="text/javascript" src="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/swfobject.js"></script>
	<script type="text/javascript" src="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/jquery.strobemediaplayback.js"></script>

	<script type="text/javascript">
		var isiPad = false;
		var isiPhone = false;
		var isAndroid = false;
		$(function(){
			isiPad = navigator.userAgent.match(/iPad/i) != null;
			isiPhone = navigator.userAgent.match(/iPhone/i) != null;
			isAndroid = navigator.userAgent.match(/Android/i) != null;

			// Get the page query string and retrieve page params
			var options={}, queryString = window.location.search;
			options.autoDynamicStreamSwitch = true;
			if (queryString) {
				queryString = queryString.substring(1);
				options = $.fn.strobemediaplayback.parseQueryString(queryString);
			}

			// Use a sample video for geeks that look at the source code for samples
			if (!options.src)
			{
				if(isiPad || isiPhone) {
					options.src = "<?php echo $videoDataSources["m3u8Url"]; ?>";
				} else if(isAndroid) {
					options.src = "<?php echo $videoDataSources["rtspUrl"]; ?>";
				} else {
					options.src = "<?php echo $videoDataSources["rtmpUrl"]; ?>";
				}
			}

			<?php
				if($_GET["autoPlay"])
					echo "options.autoPlay = true;";
				if($_GET["muted"])
					echo "options.muted = true;";
				if($_GET["hideControls"])
					echo "options.controlBarMode = 'none';";
				if($_GET["wmode"])
				{
					switch($_GET["wmode"])
					{
						case "window" :
							echo "options.wmode = 'window';";
							break;
						case "opaque" :
							echo "options.wmode = 'opaque';";
							break;
						default :
							break;
					}
				}
			?>

			options = $.fn.adaptiveexperienceconfigurator.adapt(options);

			// Retrieve the window dimensions
			options.width = $(window).width();
			options.height = $(window).height();
			// Now we are ready to generate the video tags
			$("#player").strobemediaplayback(options);
		});
	</script>
</head>
<body>
<div id="player">If you're seeing this then we are having problems adapting to your browsers requirements for viewing video. Please try one of the following links:</div> 
</body>
</html>

