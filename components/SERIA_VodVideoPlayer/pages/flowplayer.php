<?php
	$object = SERIA_NamedObjects::getInstanceByPublicId($_GET["objectKey"]);

	$streamInfo = $object->getStreamPoint();

	$flashVars = array(
		'wmode',
	);

	$flashVarsString = '';
	foreach($flashVars as $flashVar) {
		if(isset($_GET[$flashVar])) {
			$flashVarsString.= ','.$flashVar.':"'.$_GET[$flashVar].'"';
		}
	}

	$settings = '{
	clip: {
		autoPlay: true,
		scaling: "stretch",
		url: "dnb;009-1280x720-betal-innland-u-kid-sub.flv",
		live: false,
		provider: "rtmp",
		onMetaData: window.onMetaData
	},
	plugins: {
		controls: null,
		rtmp: {
			url: SERIA_VARS.HTTP_ROOT + "/seria/components/SERIA_VodVideoPlayer/bin/flowplayer/flowplayer.rtmp-3.2.10.swf",
			netConnectionUrl: "rtmp://streaming.seria.net/vod/",
		},
		slowmotion: {
			url: SERIA_VARS.HTTP_ROOT + "/seria/components/SERIA_VodVideoPlayer/bin/flowplayer/flowplayer.slowmotion-3.2.9.swf",
			serverType: "fms"
	},
		content: {
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
		},
		speedIndicator: {
			 url: SERIA_VARS.HTTP_ROOT + "/seria/components/SERIA_VodVideoPlayer/bin/flowplayer/flowplayer.content-3.2.8.swf",
			bottom: 10,
			right: 15,
			width: 135,
			height: 30,
			border: "none",
			style: {
				body: {
					fontSize: 12,
					fontFamily: "Helvetica",
					textAlign: "center",
					color: "#ffffff"
				}
			},
 
backgroundColor: "rgba(20, 20, 20, 0.5)",
 
// we don"t want the plugin to be displayed by default,
// only when a speed change occur.
display: "none"
},
	},
	onLoad: window.onLoad,
	onStart: window.onStart,
	onStop: window.onStop,
	onFinish: window.onFinish,
	onFullscreen: window.onFullscreen,
	onFullscreenExit: window.onFullscreenExit,
	onError: window.onError
}';

?><!DOCTYPE html>
<html><head>
	<title><?php echo $data['title']; ?></title>
	<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_LiveVideoPlayer/js/easyXDM.min.js' type='text/javascript' language='javascript'></script>
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
var actions = {
	backward: function(speed) {
		$f().getPlugin('slowmotion').backward(speed);
	},
	forward: function(speed) {
		$f().getPlugin('slowmotion').forward(speed);
	},
	normal: function() {
		$f().getPlugin('slowmotion').normal();
	}
}

window.actions = actions;
/* SHARED EVENT CALLBACKS. These are called by the current player to update information for apis. */
// Called immediately after initializing is finished. The API must be fully available.
function onLoad() {
	socket.postMessage("event:load");
}
function onFullscreen() { socket.postMessage("event:fullscreen"); }
function onFullscreenExit() { socket.postMessage("event:fullscreenExit"); }
function onError(errorCode, errorMessage) { socket.postMessage("event:error:");
//(" + errorCode + ", " + errorMessage + ")"); 
}
function onStart() {
	var self = this;
	var currentTimeInterval = setInterval(function() {
		onCurrentTimeChange(self.getTime());
		if (self.getState() == 5)
			clearInterval(currentTimeInterval);
	}, 500);
	socket.postMessage("event:start");
}
function onMetaData(metadata) {
	duration = parseInt(metadata.duration);
	socket.postMessage("duration:" + parseInt(metadata.duration));
}
function onStop() { socket.postMessage("event:stop"); }
function onPause() { socket.postMessage("event:pause"); }
function onResume() { socket.postMessage("event:resume"); }
function onFinish() { socket.postMessage("event:finish"); }

// Easy XDM

	var socket = new easyXDM.Socket({
	onMessage: messageReceived,
	onReady : function() {
		socket.postMessage("hello");
	}
	});

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
			case "forward" :
				if(param)
					actions.forward(param);
				else
					actions.forward(2);
				break;
			case "backward" :
				if(param)
					actions.backward(param);
				else
					actions.backward(2);
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
			$f(0).play();
		}
	}

	function seek(seconds)
	{
		var obj = getVideoObject();
		if(usingHtml) {
			obj.currentTime = seconds;
		} else {
			$f().seek(seconds);
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
		if(usingHtml) {
			getVideoObject().pause();
		} else {
			$f().pause();
		}
	}

	var usingHtml =false;
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
			return duration; // getVideoObject().getClip(0).duration;
		}
	}

	var currentFlashPlayer;
	// Defines the javascript bridge between the flash player and this script

	function seeking()
	{
		socket.postMessage("event:seeked");
	}

	function onComplete()
	{
		socket.postMessage("event:finished");
	}

	function onStateChange(state)
	{
		switch(state) {
			case "paused" :	
				socket.postMessage("event:pause");
				break;	
			case "playing" :
				socket.postMessage("event:playing");
				break;
			case "finished" :
				socket.postMessage("event:finished");
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
			socket.postMessage("event:seeked");
		else if((currentTime-newTime)>10) {
			socket.postMessage("event:seeked");
		} else {
		}

		if(currentTime != newTime) {
			seenMap[newTime] = "1";
			socket.postMessage("time:"+currentTime);
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
				} else
					if(oldState != currentFlashPlayer.getState())
					{
				oldState = currentFlashPlayer.getState();
				onStateChange(oldState);
					}
			}
		} catch (e) {
			alert(e);
		}
	}

	var stateChangeInterval;
	$(function() {
		stateChangeInterval = setInterval(checkStateChanged, 1000);
	});
	$(window).unload(function() {
		$('#player').remove();
	});


	</script>
	<link href="http://vjs.zencdn.net/c/video-js.css" rel="stylesheet">
	<script src="http://vjs.zencdn.net/c/video.js"></script>
	<script src="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_LiveVideoPlayer/bin/flowplayer/flowplayer-3.2.11.min.js"></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
</head><body>
	<div id="player" class="player">
		<img id="playbutton" src="<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_LiveVideoPlayer/assets/play.png">
		<img id="poster" src="<?php echo SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/assets/temp_image_do_not_use.png'; ?>" style="width: 100%; height: 100%;">
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
	player_flowPlayer = flowplayer("player", { src: "<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_LiveVideoPlayer/bin/flowplayer-3.2.12.swf" <?php echo $flashVarsString; ?> }, <?php echo $settings; ?>);
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
	}

	<?php if(isset($_GET['autoPlay'])) echo "showPlayer();"; else echo '$(window).click(showPlayer);'; ?>
}
	</script>
</body></html>
