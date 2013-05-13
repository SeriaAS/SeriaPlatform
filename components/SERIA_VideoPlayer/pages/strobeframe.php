<?php
	$http = 'http'.(empty($_SERVER['HTTPS']) || $_SERVER['HTTPS']=='off' ? '' : 's');
	if(isset($_GET['admin']) && SERIA_Base::isLoggedIn())
		SERIA_Base::viewMode('system');
	try {
		if(!is_numeric($_GET['objectKey'])) { echo "Not published."; return; }

		$video = SERIA_NamedObjects::getInstanceByPublicId($_GET['objectKey'], 'SERIA_IVideoData');
	} catch (SERIA_Exception $e) {
		SERIA_Base::viewMode('admin');
		try {
			$video = SERIA_NamedObjects::getInstanceByPublicId($_GET['objectKey'], 'SERIA_IVideoData');
			echo "Not published"; 
			return;
		} catch (SERIA_Exception $e) {
			echo "Not found";
			return;
		}
	}
	if(!SERIA_Base::isLoggedIn() && $video->get('isPrivate')) {
		if(!isset($_GET['clientId']) || !isset($_GET['sign'])) die("Unsigned request - expecting clientId and sign");
		if(isset($_GET['expires']) && time()>intval($_GET['expires'])) die("URL has expired");
		$client = SERIA_Fluent::all('SERIA_RPCClientKey')->where('client_id='.intval($_GET['clientId']))->current();
		if(!$client) die("Invalid client id");
		if(!SERIA_Url::current()->isSigned($client->get('client_key')))
			die("Invalid signature");
	}

	$rawurl = parse_url($_SERVER['HTTP_REFERER']);
	$hostname = $rawurl['host'];
	$hostArray = explode('.', $hostname);
	$subdomain = $hostArray[0];

	if(file_exists(SERIA_ROOT.'/sites/'.$subdomain[0].'/'.$hostname.'/rules.php')) {
		include(SERIA_ROOT.'/sites/'.$subdomain[0].'/'.$hostname.'/rules.php');
	}

	$domain = str_replace('www.', '', $hostname);

	$counter = new SERIA_Counter('SeriaWebTVStatistics');
	$counter->add(array('Referer:'.$video->get("id").':'.date('Y-m-d').':'.$domain),1);

	if(is_a($video, 'SERIA_LiveProgram')) {
		$counter = new SERIA_Counter('SeriaLiveStatistics');
		$counter->add(array(
			'Ym/i:'.date('Ym', time()).'/'.$video->get("id"),
			'Ymd/i:'.date('Ymd', time()).'/'.$video->get("id"),
			'YmdH/i:'.date('YmdH', time()).'/'.$video->get("id"),
		));
		$isLive = true;
	}

	$vd = $video->getVideoData();

	function getBestFlashVideoSource($videoSources, $id)
	{
		if($_SERVER['HTTP_HOST'] == "dna.seriatv.com") {
			return urlencode("rtmp://live.istribute.com/live/dna1");
			return urlencode("http://dna.seriatv.com/dnamanifest.f4m?r=42");
}
		foreach($videoSources as $i => $source)
		{
			if(strpos($source['src'], "rtmp") === false) {
				if(strpos($source['src'], "f4m") === false) {
					unset($videoSources[$i]);
				}
			}
		}
		if(!sizeof($videoSources))
			throw new SERIA_Exception('Unable to find any video source for this video. This may be because the files are beeing transcoded, please try again in a moment');
		$selectedSource = array_pop($videoSources);

		return $selectedSource['src'];
	}

	$flashVideoSource = getBestFlashVideoSource($vd['sources'], $video->get("id"));

	$sources = $vd['sources'];
	$source = current($sources);
	$source = $source['src'];

	$backgroundColor = (isset($_GET["backgroundColor"]) ? $_GET["backgroundColor"] : "000000");

	function getRelativeTime($time)
	{
		$totalTime = 0;
		$tArr = explode(":", $time);
		$totalTime = $tArr[0]*3600;
		$totalTime+= $tArr[1]*60;
		$totalTime+= $tArr[2];

		return $totalTime;
	}

	if($_GET["clipStartTime"] && (strpos($_GET["clipStartTime"], ":") !== false)) {
		$startTime = getRelativeTime($_GET["clipStartTime"]);
	} else if($_GET["clipStartTime"]) {
		$startTime = intval($_GET["clipStartTime"]);
	} else {
		$startTime = false;
	}

	if($_GET["clipEndTime"] && (strpos($_GET["clipEndTime"], ":") !== false)) {
		$stopTime = getRelativeTime($_GET["clipEndTime"]);
	} else if($_GET["clipEndTime"]) {
		$stopTime = intval($_GET["clipEndTime"]);
	} else {
		$stopTime = false;
	}
	if($isLive) {
		$flashVars = array(
			'autoplay' => (isset($_GET['autoplay']) ? 'true' : 0),
			'autoPlay' => (isset($_GET['autoPlay']) ? 'true' : 0),
			'src' => $flashVideoSource,
			'poster' => $vd['previewImage'],
			'bufferTime' => 10,
			'initialBufferTime' => 2,
			'__r' => mt_rand(10000,99999)
//			'scaleMode' => 'letterbox'
		);
	} else {
		$flashVars = array(
			'autoplay' => (isset($_GET['autoplay']) ? 'true' : 0),
			'autoPlay' => (isset($_GET['autoPlay']) ? 'true' : 0),
			'backgroundColor' => $backgroundColor,
			'hideControls' => (isset($_GET['hideControls']) ? 1 : 0),
			'src' => $flashVideoSource,
			'clipStartTime' => $startTime,
			'clipEndTime' => $stopTime,
			'poster' => $vd['previewImage'],
			'bufferingOverlay' => "false",
			'bufferTime' => 10,
			'initialBufferTime' => 2,
			'streamType' => ($isLive ? 'live' : 'auto'),
			'javascriptCallbackFunction' => 'onJavaScriptBridgeCreated'
		);
	}
	$newSourcesArray = array();

/*
	if(SERIA_BrowserInfo::current()->supportsRtsp()) {
		foreach($sources as $source) {
			if((strpos($source['src'], 'rtsp') === 0) || (strpos($source['src'], 'http') === 0)) { //RTSP PROTOCOL
				$newSourcesArray[] = $source;
			}
		}
		$sources = $newSourcesArray;
	}
*/

	function arrayToFlash($flashvarsArray)
	{
		$flashvarString = '';
		foreach($flashvarsArray as $key => $val) {
			if(!empty($val) && ($val != '') && isset($val))
				$flashvarString .= $key.'='.$val.'&';
		}
		return $flashvarString;
	}

	$flashVars = arrayToFlash($flashVars);

?><!DOCTYPE html>
<html style="height:100%">
	<head>
		<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
		<title>Seria WebTV Player for embedding</title>
		<link rel='stylesheet' href='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/player.css?<?php echo mt_rand();?>' type='text/css'>
		<script type='text/javascript' language='javascript'>

			window.videoData = {
				poster: <?php echo SERIA_Lib::toJSON($vd['previewImage']); ?>,
				sources: <?php echo SERIA_Lib::toJSON($vd['sources']); ?>,
				objectKey: <?php echo intval($_GET['objectKey']); ?>
			};
		</script>
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/platform/js/SERIA.js' type='text/javascript'></script>
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/flash_detect.js' type='text/javascript' language='javascript'></script>
<?php /*		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/player.js?<?php echo mt_rand();?>' type='text/javascript' language='javascript'></script> */ ?>

		<script src='<?php echo $http; ?>://ajax.microsoft.com/ajax/jquery/jquery-1.5.min.js' type='text/javascript' language='javascript'></script>
		<script type='text/javascript'>


			var usingHtml =  false;
			var videoObject = null;
			function getVideoObject()
			{
				if(videoObject == null) {
					if(document.getElementsByTagName('video')[0] != undefined) {
						videoObject = document.getElementsByTagName('video')[0];
						usingHtml = true;
					} else {
						videoObject = document.getElementById('flash');
					}
				}
				return videoObject;
			}

			var duration = 0;
			function getVideoDuration()
			{
				if(usingHtml) {
					return duration = videoObject.duration;
				} else {
					return duration;
				}
			}
			function onDurationChange(dur)
			{
				duration = parseInt(dur);
			}



			var currentFlashPlayer;
			function onJavaScriptBridgeCreated()
			{
				if(currentFlashPlayer == null)
				{
					currentFlashPlayer = document.getElementById('flash');
					currentFlashPlayer.addEventListener("currentTimeChange", "onCurrentTimeChange");
					currentFlashPlayer.addEventListener("durationChange", "onDurationChange");
					//currentFlashPlayer.addEventListener("stateChange", "onStateChange");
					//currentFlashPlayer.addEventListener("complete", "onComplete");
				}
			}

			var currentTime = 0;
			var seenMap = new Object();
			var seenMapSetup = false;
			var shouldSpool = <?php if(isset($_GET["aclipStartTime"])) echo "true"; else echo "false"; ?>;
			function onCurrentTimeChange(time)
			{
				if(shouldSpool) {
					if(usingHtml) {
						getVideoObject().currentTime = <?php if($_GET["aclipStartTime"]) echo $_GET["aclipStartTime"]; else echo 0; ?>;
					} else {
						getVideoObject().seek(<?php echo $_GET["aclipStartTime"]; ?>);
					}
					shouldSpool = false;
				}
				if(!getVideoDuration())
					return;
				if(!seenMapSetup)
				{
					for(var i=0;i<getVideoDuration();i++)
					{
						seenMap[i] = "0";
					}
					seenMapSetup = true;
					setInterval(registerSeenMap, 10000);
				}
				var newTime = parseInt(time);

				if(currentTime != newTime) {
					seenMap[newTime] = "1";
				}
				currentTime = newTime;
			}

			function registerSeenMap()
			{
<?php
				if(isset($_GET["euid"])) {
					$url = new SERIA_Url(SERIA_HTTP_ROOT."/seria/components/SERIA_VideoPlayer/api/registerVideoVisitorStats.php");
					$signedUrl = $url->sign($video->get("id").SERIA_FILES_ROOT.SERIA_DB_PASSWORD);

					echo '
				var seenMapString = "";
				for(var b in seenMap)
					seenMapString+=seenMap[b] + "";

				$.ajax({
					url: "'.$signedUrl.'",
					type: "POST",
					data: "seenMap="+seenMapString+"&vid='.$video->get("id").'&euid='.$_GET["euid"].'",
					success : function(e) {
					},
					error : function(e) {
					}
				});
		';
	}
?>
			}


setInterval(function(){
	$('video').css({
		position: "absolute",
		top: "0px",
		left: "0px"
	});
}, 1000);

function showAlternatives(sources) {
var found = new Object();
for(var i in sources) {
if(sources[i].src.match(/^rtsp/))
found.android = sources[i].src;
else if(sources[i].src.match(/^http/) && (window.videoData.sources[i].src.match(/.mp4$/i) || window.videoData.sources[i].src.match(/.m3u8$/i)))
found.ios = sources[i].src;
}
if(found.android || found.ios) {
html = 'Unable to autoplay video for you: <br>';
if(found.android) html += 'Play with <a href="' + found.android + '">Android</a><br>';
if(found.ios) html += 'Play with <a href="' + found.ios + '">iOS</a><br>';
jQuery('#fallback').html(html);
}
}


jQuery(function(){
	if(FlashDetect.versionAtLeast(10, 1)) {

	} else {
		jQuery('#fallback').html("<?php echo _t("Please upgrade your Flash Version at <a href='http://get.adobe.com/flashplayer/' target='_blank'>http://get.adobe.com/flashplayer/</a>"); ?>");
	}
	if(!FlashDetect.installed) {
		// detect if html5 video is supported
		var v = document.createElement('video');
		if(!v.canPlayType)
		{
			jQuery('#fallback').html("<?php echo _t("Please install Adobe Flash player or upgrade to a newer browser that supports HTML 5 video"); ?>");
		}
		else if(navigator.userAgent.indexOf('Mobile Safari')==-1 && !v.canPlayType('video/mp4') && !v.canPlayType('video/webm; codecs="vp8, vorbis"') && !v.canPlayType('video/webm; codecs="vp8, vorbis"'))
		{
//		else if(!v.canPlayType('video/mp4; codecs="avc1.42E01E, mp4a.40.2"') && !v.canPlayType('video/webm; codecs="vp8, vorbis"') && !v.canPlayType('video/webm; codecs="vp8, vorbis"'))
			jQuery('#fallback').html("<?php echo _t("Your browser does not support mp4, webm or Ogg/Theora video compression"); ?>");
		}
		else
		{
			jQuery('#fallback').html("<?php echo _t("Loading video player"); ?>");
			v.style.height = '100%';
			v.style.width = '100%';
			<?php if(isset($_GET['hideControls'])) echo "v.controls = false;"; else echo "v.controls = true;\r\n"; ?>
			<?php if(isset($_GET['autoplay'])) echo "v.autoplay = true;"; else echo "v.autoplay = false;\r\n"; ?>

			v.style.backgroundColor = '#<?php echo $backgroundColor; ?>';
			if(window.videoData.poster)
				v.poster = window.videoData.poster;
			var i;

			var oldTime = 0;
			v.addEventListener("timeupdate", function(ev) {
				var curTime = parseInt(v.currentTime);
				oldTime = curTime;
				onCurrentTimeChange(curTime);
			}, false);

			if(navigator.userAgent.match(/like Mac OS X/i))
			{
				var src;
				// use the first playable source
				for(i in window.videoData.sources)
				{
					if(!src && window.videoData.sources[i].src.match(/^http/))
					{
						if(window.videoData.sources[i].src.match(/.m3u8$/i) && parseInt(jQuery.browser.version)>=533) // earlier versions than 533 does not support m3u8 bitrate switching?
							src = window.videoData.sources[i].src;
						else if(window.videoData.sources[i].src.match(/.mp4$/i))
							src = window.videoData.sources[i].src;
					}
				}
				if(!src)
				{
					jQuery('#fallback').html("<?php echo _t("I was unable to find a suitable video format for you"); ?>");
				}
				else
				{
					v.src = src;
					jQuery('#fallback').replaceWith(v);
				}
			}
			else if
			(
				navigator.userAgent.match(/android/i) ||				// Android mobile devices
				(jQuery.browser.msie && jQuery.browser.version>=9) ||			// Microsoft Internet Explorer 9
				(jQuery.browser.webkit && /chrome/i.test(navigator.userAgent)) ||	// Google Chrome
				(jQuery.browser.webkit && /safari/i.test(navigator.userAgent))		// Safari
			)
			{
				var rtspSrc;
				var httpSrc;
				for(i in window.videoData.sources)
				{
					// && window.videoData.sources[i].src.match(/.mp4$/i))
					if(!rtspSrc && window.videoData.sources[i].src.match(/^rtsp/))
					{
						rtspSrc = window.videoData.sources[i].src;
					}
					if(!httpSrc && window.videoData.sources[i].src.match(/^http/) && window.videoData.sources[i].src.match(/.mp4$/i))
					{
						httpSrc = window.videoData.sources[i].src;
					}
				}

				if(!httpSrc)
				{
					jQuery('#fallback').html("<?php echo _t("I was unable to find a suitable video format for you"); ?>");
					showAlternatives(window.videoData.sources);
				}
				else
				{
					v.autobuffer = false;
					v.preload = 'none';
					v.src = httpSrc;
					jQuery(v).dblclick(function(){
						try {
							if(this.enterFullscreen) this.enterFullscreen();
							else if(this.webkitEnterFullscreen) this.webkitEnterFullscreen();
							else throw "No fullscreen for you my dear";
						} catch(e) {
							alert("<?php echo _t("Your browser does not support fullscreen video without Adobe Flash player"); ?>");
						}
					});
					// android devices does not currently have a play button when controls is enabled
					if(navigator.userAgent.match(/android/i))
						jQuery(v).click(function(){this.play();});
					jQuery('#fallback').replaceWith(v);
				}
/*
				if(!httpSrc && !rtspSrc)
				{
					jQuery('#fallback').html("<?php echo _t("I was unable to find a suitable video format for you"); ?>");
				}
				else
				{
					if(rtspSrc) {
						var rs = document.createElement('source');
						rs.src = rtspSrc;
						v.appendChild(rs);
					}
					if(httpSrc) {
						var hs = document.createElement('source');
						hs.src = httpSrc;
						v.appendChild(hs);
					}
//					v.src = httpSrc;
					jQuery(v).click(function(){this.play();});
					jQuery('#fallback').replaceWith(v);
//alert(v);
				}
*/
			}
			else if(jQuery.browser.opera)
			{ // generic solution where browser auto detects
				jQuery('#fallback').html("<?php echo _t("I was unable to find a suitable video format for you. Install Adobe Flash player or upgrade your browser."); ?>");
//alert('opera ' + jQuery.browser.version);
			}
			else if(jQuery.browser.firefox)
			{
				jQuery('#fallback').html("<?php echo _t("I was unable to find a suitable video format for you. Install Adobe Flash player or upgrade your browser."); ?>");
//alert('firefox ' + jQuery.browser.version);
			}
			else
			{
				var s;
				for(i in window.videoData.sources)
				{
					s = document.createElement('source');
					s.src = window.videoData.sources[i].src;
					v.appendChild(s);
				}
				jQuery('#fallback').replaceWith(v);
			}
		}
	}

});
		</script>
  <style>
    @-o-viewport { width: device-width; }
    @-moz-viewport { width: device-width; }
    @-ms-viewport { width: device-width; }
    @-webkit-viewport { width: device-width; }
    @viewport { width: device-width; }
  </style>
<?php
	if($_GET["euid"]) {
echo '
	<script type="text/javascript">
		$(function() {
			$(window).unload(function() {
				registerSeenMap();
			});
		});
	</script>';
	}
?>
	</head>
	<body style="background-color: #<?php echo $backgroundColor; ?>; height: 100%;">
<?php
		if(defined('SERIA_VIDEOPLAYER_SKIN')) require(SERIA_VIDEOPLAYER_SKIN);
		else require(SERIA_ROOT.'/seria/components/SERIA_VideoPlayer/assets/skin.php');

		// Let's find out if this site has a custom videoplayer 

		$swfRoot = SERIA_HTTP_ROOT.'/seria/components/SERIA_VideoPlayer/bin/StrobeMediaPlayback.swf';
/*
		if($isLive) {
			$swfRoot = SERIA_HTTP_ROOT.'/seria/components/SERIA_VideoPlayer/bin/StrobeLive.swf';
			//$swfRoot = "http://www.osmf.org/dev/1.5gm/StrobeMediaPlayback.swf";
		}
*/
		if(isset($_GET['opaque']))
			$wmode = 'opaque';
		else if(isset($_GET["transparent"]))
			$wmode = 'transparent';
		else $wmode = 'window';

		// HACK :
		// Should incorporate site-specific settings for wmode, bgcolor etc.
		$wmode = 'window';

		if(isset($_GET['opaque']))
			$wmode = 'opaque';

			echo "
			<!--[if IE]>
			<object id='flash' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='100%' height='100%'>
	<div id='fallback' style='color:#fff;font-family:Arial,sans-serif;width:100%;height:100%;padding:20px;-moz-box-sizing:border-box;box-sizing:border-box;'>"._t("Unable to play video. Your browser does not support Adobe Flash and has Javascript disabled.")."</div>
				<param name='movie' value='".$swfRoot."'></param>
				<param name='allowFullscreen' value='true'></param>
				<param name='wmode' value='".$wmode."'></param>
				<param name='allowscriptaccess' value='always'></param>
				<param name='flashvars' value='".$flashVars."'></param>
			</object>
			<![endif]-->
			<!--[if !IE]>-->
			<object id='flash' type='application/x-shockwave-flash' data='".$swfRoot."' width='100%' height='100%'>
	<div id='fallback' style='color:#fff;font-family:Arial,sans-serif;width:100%;height:100%;padding:20px;-moz-box-sizing:border-box;box-sizing:border-box;'>"._t("Unable to play video. Your browser does not support Adobe Flash and has Javascript disabled.")."</div>
			<param name='flashvars' value='".$flashVars."'></param>
			<param name='allowscriptaccess' value='always'></param>
			<param name='wmode' value='".$wmode."'></param>
			<param name='allowFullscreen' value='true'></param>
		</object>
		<!--<![endif]-->
	";

?>
	</body>
</html>
