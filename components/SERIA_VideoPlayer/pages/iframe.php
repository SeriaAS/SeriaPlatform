<?php
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

	$rawurl = parse_url($_SERVER['HTTP_REFERER']);
	$hostname = $rawurl['host'];
	$domain = str_replace('www.', '', $hostname);

	$counter = new SERIA_Counter('SeriaWebTVStatistics');
	$counter->add(array('Referer:'.$video->get("id").':'.date('Y-m-d').':'.$domain),1);

	$vd = $video->getVideoData();
	$sources = $vd['sources'];
	$source = current($sources);
	$source = $source['src'];

	$backgroundColor = (isset($_GET["backgroundColor"]) ? $_GET["backgroundColor"] : "000000");

	$flashVars = array(
		'httpRoot' => rawurlencode(SERIA_HTTP_ROOT),
		'objectKey' => $_GET['objectKey'],
		'debugMode' => ((SERIA_Base::isLoggedIn() && $_GET["debugMode"]) ? 'true' : ''),
		'autoplay' => (isset($_GET['autoplay']) ? 1 : 0),
		'backgroundColor' => $backgroundColor,
		'hideControls' => (isset($_GET['hideControls']) ? 1 : 0),
	);
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
<html>
	<head>
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
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/js/easyXDM.min.js' type='text/javascript'></script>
		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/flash_detect.js' type='text/javascript' language='javascript'></script>
<?php /*		<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/player.js?<?php echo mt_rand();?>' type='text/javascript' language='javascript'></script> */ ?>
		<script src='http://ajax.microsoft.com/ajax/jquery/jquery-1.5.min.js' type='text/javascript' language='javascript'></script>
		<script type='text/javascript'>
			// VIDEO CONTROLS
			var i = 0;
			var playWhenReady;
			function play()
			{
				if(typeof v !== "undefined") {
					// Using HTML5 Video
					v.load();
					playWhenReady = function() {
						if(i==10) {
							i=0;
							v.load();
						}
						if(v.readyState > 1) {
							v.play();
						} else {
							i++;
							setTimeout(playWhenReady, 800);
						}
					};
					setTimeout(playWhenReady, 800);
				} else {
					// Using Flash based video
				}
			}

			function fullscreen() {
				if(typeof v !== "undefined") {
					// Using HTML5
					v.webkitEnterFullscreen();
				} else {
					// Using flash
				}
			}
		</script>
		<script type='text/javascript'>
jQuery(function(){
	if(!FlashDetect.installed) {
		// detect if html5 video is supported
		v = document.createElement('video');
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
					if(!rtspSrc && window.videoData.sources[i].src.match(/^rtsp/) && window.videoData.sources[i].src.match(/.mp4$/i))
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
					if(s.src.indexOf('rtmp')===-1)
						v.appendChild(s);
				}
				jQuery('#fallback').replaceWith(v);
			}
		}
	}

});
		</script>
	</head>
	<body>
<?php
		if(defined('SERIA_VIDEOPLAYER_SKIN')) require(SERIA_VIDEOPLAYER_SKIN);
		else require(SERIA_ROOT.'/seria/components/SERIA_VideoPlayer/assets/skin.php');

		// Let's find out if this site has a custom videoplayer 

		if(file_exists(SERIA_DYN_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf')) {
			$swfRoot = SERIA_DYN_HTTP_ROOT.'/SERIA_VideoPlayer/SeriaPlayer.swf';
		} else {
			$swfRoot = SERIA_HTTP_ROOT.'/seria/components/SERIA_VideoPlayer/bin/SeriaPlayer.swf';
		}

?>
		<!--[if IE]>
		<object id='flash' classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='100%' height='100%'>
<div id='fallback' style='color:#fff;font-family:Arial,sans-serif;width:100%;height:100%;padding:20px;-moz-box-sizing:border-box;box-sizing:border-box;'><?php echo _t("Unable to play video. Your browser does not support Adobe Flash and has Javascript disabled."); ?></div>
			<param name='movie' value='<?php echo $swfRoot; ?>'></param>
			<param name='allowFullscreen' value='true'></param>
			<param name='wmode' value='<?php echo isset($_GET['opaque']) ? 'opaque' : 'window'; ?>'></param>
			<param name='allowscriptaccess' value='always'></param>
			<param name='flashvars' value='<?php echo $flashVars; ?>&sks=true'></param>
		</object>
		<![endif]-->
		<!--[if !IE]>-->
		<object id='flash' type='application/x-shockwave-flash' data='<?php echo $swfRoot; ?>' width='100%' height='100%'>
<div id='fallback' style='color:#fff;font-family:Arial,sans-serif;width:100%;height:100%;padding:20px;-moz-box-sizing:border-box;box-sizing:border-box;'><?php echo _t("Unable to play video. Your browser does not support Adobe Flash and has Javascript disabled."); ?></div>
			<param name='flashvars' value='<?php echo $flashVars; ?>&sks=true'></param>
			<param name='allowscriptaccess' value='always'></param>
			<param name='wmode' value='<?php echo isset($_GET['opaque']) ? 'opaque' : 'window'; ?>'></param>
			<param name='allowFullscreen' value='true'></param>
		</object>
		<!--<![endif]-->
<?php
		// XDM is used to allow an embedding site to run certain javascript functions on the embedded iframe.
		// 
?>
		<script type="text/javascript" language="javascript">
			var socket = new easyXDM.Socket({
				  onMessage: function(message, origin) {
					window[message]();
				}
			});

		</script>
	</body>
</html>
