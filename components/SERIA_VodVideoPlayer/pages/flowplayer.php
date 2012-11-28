<?php
/*
Typical event order:

onLoad
onStart
onStop


MUST SUPPORT:
$_GET['startTime'] number of seconds to skip
$_GET['autoplay'] automatically start playing
$_GET['nocontrols'] hide all control bars
$_GET['rangeStartTime'] cut beginning with the number of seconds
$_GET['rangeStopTime'] cut end of at second

FUTURE:
$_GET['playlist'] = url of a playlist file hosted at the client server. Will play the videos in order.

INFO:
$app	Application instance available
$video 	Current Video object
$data	Array containing path to videos and protocols
*/
        $object = SERIA_NamedObjects::getInstanceByPublicId($_GET["objectKey"]);

        $videoFiles = $object->getVodVideoData();

	$flashVars = array(
		'wmode',
	);

	$flashVarsString = '';
	foreach($flashVars as $flashVar) {
		if(isset($_GET[$flashVar])) {
			$flashVarsString.= ','.$flashVar.':"'.$_GET[$flashVar].'"';
		}
	}

/*
	$flowPlayer = array(
		"clip" => array(
			'bitrates' => array(),
		),
	);

	$heightToBitrate = array(
		360 => 300,
		480 => 750,
		720 => 1300,
		1080 => 2500,
	);
	$maxSize = 0;
	foreach(array(360, 480, 720, 1080) as $height) {
		foreach(array('mp4') as $format) {
			$key = $format.'_'.$height;
			if($video->$key) {
		if($maxSize < $height) $maxSize = $height;
		$pixels = $height*($height*16/9);
		$x = sqrt($pixels*0.5654);
		$flowPlayer['clip']['bitrates'][] = array(
			'width' => intval($x),
			'bitrate' => $heightToBitrate[$height],
			'url' => 'mp4:'.$app->slug.'/'.$video->slug.'/'.$height.".mp4",
		);
			}
		}
	}
	if($maxSize>360) $maxSize = 360;
	$flowPlayer['clip']['bitrates'][0]['isDefault'] = TRUE;
*/
$settings = array(
	"showErrors" => "true",
	"debug" => "true",
	"clip" => array(
		"autoPlay" => "true",
		"scaling" => "stretch",
		"url" => $videoFiles[0]['src'],
		"live" => "false",
		"provider" => "httpstreaming",
		"onStart" => 'function() {
			this.getPlugin("play").hide();
		}',
		"onBeforeFinish" => 'function() {
			this.getPlugin("play").show();
		}'
	),
	"plugins" => array(
		"httpstreaming" => array(
			"url" => SERIA_HTTP_ROOT."/seria/components/SERIA_VodVideoPlayer/bin/flowplayer.httpstreaming-3.2.8.swf"
		),
		"controls" => null,
	),
/*
	"content" => array(
			url: SERIA_VARS.HTTP_ROOT + "/seria/components/SERIA_VodVideoPlayer/bin/flowplayer/flowplayer.content-3.2.8.swf",
			top: 0, left: 0, width: 250, height: 150,
			backgroundColor: "transparent", backgroundGradient: "none", border: 0,
			textDecoration: "outline",
			style: {
		body: {
			fontSize: 14,
			fontFamily: "Arial",
			textAlign: "center",
			color: "#ffffff"
		}
			}
		}
	},
*/
	"onLoad" => "window.onLoad",
	"onStart" => "window.onStart",
	"onStop" => "window.onStop",
	"onFinish" => "window.onFinish",
	"onFullscreen" => "window.onFullscreen",
	"onFullscreenExit" => "window.onFullscreenExit",
	"onError" => "window.onError"
);


?><!DOCTYPE html>
<html><head>
	<title><?php echo $data['title']; ?> - Istribute.com</title>
	<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VodVideoPlayer/js/easyXDM.min.js' type='text/javascript' language='javascript'></script>
	<style type='text/css'>
html, body, #player, #poster { background-color: #000; height: 100%; width: 100%; margin: 0; padding: 0; border-width:0; box-sizing: border-box; overflow: hidden; color: #fff;}
#player video {
	width:100%;
	height: 100%;
}
#playbutton {
	position: absolute;
	background-color: #000;
	top: 50%;
	left: 50%;
	margin-top: -16px;
	margin-left: -16px;
	opacity: 0.6;
}
body:hover #playbutton { opacity: 1; }
	</style>
	<script>
if(typeof console!="object") var console = { log: function() {} };
function apiPost(message) {
	console.log("Sending message: " + message);
	socket.postMessage(message);
}

/* SHARED EVENT CALLBACKS. These are called by the current player to update information for apis. */
// Called immediately after initializing is finished. The API must be fully available.
function onLoad() {
	console.log("onLoad");
	loadedEventSent = true;
	clearInterval(checkLoadedInterval);
	apiPost("event:load");
}
function onFullscreen() {
	console.log("onFullscreen");
	apiPost("event:fullscreen");
}
function onFullscreenExit() {
	console.log("onFullscreenExit");
	apiPost("event:fullscreenExit");
}
function onError(errorCode, errorMessage) {
	console.log("onError(" + errorCode + ", " + errorMessage + ")");
	switch(errorCode) {
		case 200:
			onStop();
			break;
		default :
			apiPost("event:error:" + errorCode);
			break;
	}
}
function onStart(clip) {
	console.log("onStart");
	console.log($f().getClip());
	// Frode //alert("Hoyde: " + $f().getClip().height + ", Bredde: " + $f().getClip().width);
	apiPost("event:start");
}
function onStop() {
	console.log("onStop");
	apiPost("event:stop");
}
function onPause() {
	console.log("onPause");
	apiPost("event:pause");
}
function onResume() {
	console.log("onResume");
	apiPost("event:resume");
}
function onFinish() {
	console.log("onFinish");
	apiPost("event:finish");
}

// Easy XDM

	var socket = new easyXDM.Socket({
	    onMessage: messageReceived,
	    onReady : function() {
		apiPost("hello");
	    }
	});
	var loadedEventSent = false;
	function messageReceived(message)
	{
		if(message.indexOf(":")) {
			var msgArr = message.split(":");
			message = msgArr[0];
			var param = msgArr[1];
		}
		switch(message)
		{
			case "play" :
				play();
				break;
			case "pause" :
				pause();
				break;
			case "stop" :
				stop();
				break;
			case "seek" :
				seek(param);
				break;
			case "changeVolume" :
				changeVolume(param);
				break;
			default :
				alert("Unknown function: " + message);
				break;
		}
			}

	function play()
	{
		var obj = getVideoObject();
		if(usingHtml) {
			obj.play();
		} else {
//			obj.play2();
			$f(0).play();
		}
	}

	function changeVolume(vol)
	{
		var obj = getVideoObject();
		if(usingHtml) {
			obj.volume = vol;
		} else {
			$f(0).setVolume(vol);
		}
	}

	function seek(seconds)
	{
		var obj = getVideoObject();
		if(usingHtml) {
			obj.currentTime = seconds;
		} else {
			obj.seek(seconds);
		}
	}

	function stop()
	{
		var obj = getVideoObject();
		if(usingHtml) {
			getVideoObject().currentTime = 0;
			getVideoObject().pause();
		} else {
			getVideoObject().seek(0);
			getVideoObject().pause();
		}
	}

	function pause()
	{
		getVideoObject().pause();
	}

			var usingHtml =  false;
			var videoObject = null;
	function getVideoObject()
	{
		if(videoObject == null) {
			if(document.getElementsByTagName('video')[0] != undefined) {
				videoObject = document.getElementsByTagName('video')[0];
				usingHtml = true;
			} else {
				videoObject = document.getElementById('player_api');
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
	// Defines the javascript bridge between the flash player and this script
	function onJavaScriptBridgeCreated()
	{
		if(currentFlashPlayer == null)
		{
			currentFlashPlayer = document.getElementById('player_api');
			currentFlashPlayer.addEventListener("currentTimeChange", "onCurrentTimeChange");
			currentFlashPlayer.addEventListener("durationChange", "onDurationChange");
			currentFlashPlayer.addEventListener("stateChange", "onStateChange");
			currentFlashPlayer.addEventListener("complete", "onComplete");
		}
	}

	function seeking()
	{
		apiPost("event:seeked");
	}

	function onComplete()
	{
		apiPost("event:finished");
	}

	function onStateChange(state)
	{
		switch(state) {
			case "paused" :
				onPause();
//				apiPost("event:pause");
				break;
			case "playing" :
				onStart();
				apiPost("event:playing");
				break;
			case "finished" :
				FRODE
				apiPost("event:finished");
				break;
			default :
				alert("event:unknown:"+state);
				break;
		}
	}
	var currentTime = 0;
	var seenMap = new Object();
	var seenMapSetup = false;

	function onCurrentTimeChange(time)
	{
		var p = getVideoDuration();
		if(!p || p == 0) {
			console.log("No video duration");
			return;
		}
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

		if((newTime-currentTime)>10)
			apiPost("event:seeked");
		else if((currentTime-newTime)>10) {
			apiPost("event:seeked");
		} else {
		}

		if(currentTime != newTime) {
			seenMap[newTime] = "1";
			apiPost("time:"+currentTime);
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
				console.log("Result: " + e);
			},
			error : function(e) {
				console.log("Failed to submit statistics");
			}
		});
		';
	}
?>
	}


	var oldState;
	function checkStateChanged()
	{
		try {
			if((currentFlashPlayer != null) && (currentFlashPlayer != "undefined")) {
				if(oldState == null) {
					oldState = currentFlashPlayer.getState();
				} else if(oldState != currentFlashPlayer.getState()) {
					oldState = currentFlashPlayer.getState();
					onStateChange(oldState);
				}
			}
		} catch (e) {
			alert(e);
		}
	}

	function checkLoaded()
	{
		if(!loadedEventSent) {
			try {
				if(!(typeof document.getElementsByTagName("object")[0] == "undefined")) {
					loadedEventSent = true;
					window.onLoad();
				}
			} catch(e) {
				alert(e);
			}
		}
	}

	var stateChangeInterval;
	var checkLoadedInterval;
	$(function() {
		stateChangeInterval = setInterval(checkStateChanged, 1000);
		checkLoadedInterval = setInterval(checkLoaded, 1000);
	});
	$(window).unload(function() {
		$('#player').remove();
	});


	</script>
	<link href="http://vjs.zencdn.net/c/video-js.css" rel="stylesheet">
	<script src="http://vjs.zencdn.net/c/video.js"></script>
	<script src="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VodVideoPlayer/bin/flowplayer/flowplayer-3.2.11.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
</head><body>
	<div id="player" class="player">
		<img id="playbutton" src="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VodVideoPlayer/assets/play.png">
		<img id="poster" src="<?php echo SERIA_HTTP_ROOT.'/seria/components/SERIA_VodVideoPlayer/assets/temp_image_do_not_use.png'; ?>" style="width: 100%; height: 100%;">
	</div>
	<script type="text/javascript">
/**
* Use optimum resolution for poster image
*/
var h = $(window).height();
var ch = 360;
for(var vh in {360:1,480:1,720:1,1080:1})if(h>vh)ch=vh;
//$(document.getElementById("poster")).attr('src', '<?php echo $joakimposter; ?>' + ch + '.jpg');

/**
* Use flowplayer if flash is supported in this browser
*/
var player_flowPlayer;
var player_videoJS;
if(window.flashembed.isSupported([10, 1])) {
	player_flowPlayer = flowplayer("player", { src: "<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VodVideoPlayer/bin/flowplayer-3.2.12.swf" <?php echo $flashVarsString; ?> }, <?php echo json_encode($settings); ?>);
	<?php if(isset($_GET['autoPlay'])) echo "player_flowPlayer.load();"; ?>
} else {
	var showPlayer = function(){
		$(window).unbind("click", showPlayer);
		var $container = $("#player");
		var $video = $("<video id='html5player' />");
		$video.attr('autoplay', 'autoplay');
		$video.attr('controls', 'controls');
		var $sources = [];
<?php
	if(stripos($_SERVER['HTTP_USER_AGENT'], 'mobile')!==FALSE || stripos($_SERVER['HTTP_USER_AGENT'], 'ipad')!==FALSE || stripos($_SERVER['HTTP_USER_AGENT'], 'surface')!==FALSE) $res = array(360);
	else $res = array(480, 360);


	echo '$sources.push($("<source/>").attr("src", '.json_encode($streamInfo['sources']['cupertino_auto']['url']).').attr("type", "video/'.$format.'"));'."\n";
	echo '$sources.push($("<source/>").attr("src", '.json_encode('').').attr("type", "video/'.$format.'"));'."\n";
?>
		for(var i in $sources) $video.append($sources[i]);
		if(!$container.attr('data-html')) $container.attr('data-html', $container.html());
		$container.html($video);
//FRODE		apiPost("event:load");
		document.getElementById($video).addEventListener("playing", function() {
			onStateChange("playing");
		});
	}

	<?php if(isset($_GET['autoPlay'])) echo "showPlayer();"; else echo '$(window).click(showPlayer);'; ?>
}
	</script>
</body></html>
