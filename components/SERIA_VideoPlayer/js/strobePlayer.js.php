<?php
	$iframeUrl = $_GET["iframeUrl"];
	$containerId = $_GET["containerId"];
?>

/**
	USAGE; EMBEDDING VIDEO

	<script src='strobePlayer.js.php?objectKey=123'></script>

	SeriaPlayer(123).onReady = 


*/


/**
 *  Script lazy loader 0.5
 *  Copyright (c) 2008 Bob Matsuoka
 *
 *  This program is free software; you can redistribute it and/or 
 *  modify it under the terms of the GNU General Public License
 *  as published by the Free Software Foundation; either version 2
 *  of the License, or (at your option) any later version.
 */

function isLoaded(url, loadedScripts) {
	for ( var b in loadedScripts ) {
		if(loadedScripts[b] == url) {
			return true;
		}
	}
	return false;
}

var Prototype = {
  Browser: (function(){
    var ua = navigator.userAgent;
    var isOpera = Object.prototype.toString.call(window.opera) == '[object Opera]';
    return {
      IE:             !!window.attachEvent && !isOpera,
      Opera:          isOpera,
      WebKit:         ua.indexOf('AppleWebKit/') > -1,
      Gecko:          ua.indexOf('Gecko') > -1 && ua.indexOf('KHTML') === -1,
      MobileSafari:   /Apple.*Mobile/.test(ua)
    }
  })()
}
var LazyLoader = {}; //namespace
LazyLoader.timer = {};  // contains timers for scripts
LazyLoader.scripts = [];  // contains called script references
LazyLoader.load = function(url, callback) {
        // handle object or path
        var classname = null;
        var properties = null;
        try {
                // make sure we only load once
                if (isLoaded(url, LazyLoader.scripts) == false) {
                        // note that we loaded already
                        LazyLoader.scripts.push(url);
                        var script = document.createElement("script");
                        script.src = url;
                        script.type = "text/javascript";
                        document.getElementsByTagName("head")[0].appendChild(script);  // add script tag to head element
                        // was a callback requested
                        if (callback) {    
                                // test for onreadystatechange to trigger callback
                                script.onreadystatechange = function () {
                                        if (script.readyState == 'loaded' || script.readyState == 'complete') {
                                                callback();
                                        }
                                }
                                // test for onload to trigger callback
var hasLoaded = false;
if(eval("typeof " +script.onload) != "undefined") {
	hasLoaded = true;
}
                                script.onload = function () {
                                        callback();
                                        return;
                                }
                                // safari doesn't support either onload or readystate, create a timer
                                // only way to do this in safari
                                if (!hasLoaded && (Prototype.Browser.WebKit && !navigator.userAgent.match("Version/3/")) || Prototype.Browser.Opera) { // sniff
                                        LazyLoader.timer[url] = setInterval(function() {
                                                if (/loaded|complete/.test(document.readyState)) {
                                                        clearInterval(LazyLoader.timer[url]);
                                                        callback(); // call the callback handler
                                                }
                                        }, 10);
                                }
                        }
                } else {
                        if (callback) { callback(); }
                }
        } catch (e) {
                alert(e);
        }
}


















var pita;
var SeriaPlayerClass = function(objectKey, urlOptions) { this.objectKey = objectKey; this.urlOptions = urlOptions}
SeriaPlayerClass.prototype = {
	element: null,		// HOLDS THE DIV ELEMENT
	objectKey: null,	// HOLDS THE OBJECT KEY
	urlOptions : null,
	onready: null,		// WHEN THE IFRAME IS LOADED AND CAN BE COMMUNICATED WITH
	onmessage: null,	// WHEN MESSAGES THAT WE DO NOT UNDERSTAND COME
	onpause: null,		// WHEN USER PAUSES THE VIDEO (NOT WHEN SCRIPTS PAUSES THE VIDEO)
	onplay: null,
	currentTime: 0,

	// INTERNAL FUNCTIONS
	on_message: function(message) {
		if(message.indexOf('event:')===0) {
			this.dispatchEvent(message.substring(6));
		} else if(message.indexOf('time:')===0) {
			this.currentTime = parseInt(message.substring(5));
		} else if(message.indexOf('duration:')===0) {
			this.duration = parseInt(message.subsring(9));
		} else this.dispatchEvent('message', message);
	},	// WHEN THE IFRAME SENDS A MESSAGE
	on_initialize: function() {
		var self = this;
		//this.xmdSocket 
		pita = new easyXDM.Socket({
			remote: 'http://<?php echo $_SERVER['HTTP_HOST']; ?>/?route=seria%2Fvideoplayer%2Fstrobeframe_easy&objectKey=' + this.objectKey + this.urlOptions,
			container: this.element,
			onMessage: function(message) {
				if(message=="hello")				// Special message for notifying that API is available
					self.dispatchEvent("ready");
				else
					self.dispatchEvent('_message', message);
			}
		});
	},
	dispatchEvent: function(name, a1, a2, a3, a4, a5, a6) {
		if(this["on" + name]) this["on" + name].apply(this, [a1, a2, a3, a4, a5, a6]);
	},
	play : function() {
		pita.postMessage("play");
	},
	pause : function() {
		pita.postMessage("pause");
	},
	stop : function() {
		pita.postMessage("stop");
	},
	seek : function(seconds) {
		pita.postMessage("seek:"+seconds);
	}
}
SeriaPlayerClass.elements = new Array();
SeriaPlayerClass.all = new Array();
SeriaPlayerClass.allIndex = 0;
SeriaPlayerClass.allReady = new Array();

function SeriaPlayer(key, urlOptions) {
	if(!SeriaPlayerClass.all[key]) {
		SeriaPlayerClass.all[key] = new SeriaPlayerClass(); 
		SeriaPlayerClass.all[key].objectKey = key;
		SeriaPlayerClass.all[key].urlOptions = urlOptions;
	}
	return SeriaPlayerClass.all[key];
}

(function(){
	SeriaPlayerClass.allReady[SeriaPlayerClass.allIndex] = function(el) {
		var sp = SeriaPlayer(<?php echo $_GET['objectKey']; ?>, <?php 
	$getVars = $_GET;
	unset($getVars['objectKey']);
	$urlOptions = "";
	foreach($getVars as $key  => $val)
		$urlOptions.="&".$key."=".$val;

	echo "'".$urlOptions."'";
		?>);
		sp.element = el;
		sp.dispatchEvent('_initialize');
	}
	if(SeriaPlayerClass.allIndex==0) {
/*
importJs('http://ebs.seriatv.com/seria/components/SERIA_VideoPlayer/js/easyXDM.min.js', 'easyXDM', function() {
			var scripts = document.getElementsByTagName('script');
			for(var i in SeriaPlayerClass.allReady) {
				if(typeof SeriaPlayerClass.elements[i] == 'string') 
					SeriaPlayerClass.allReady[i](document.getElementById(SeriaPlayerClass.elements[i]));
				else
					SeriaPlayerClass.allReady[i](SeriaPlayerClass.elements[i]);
			}
});
*/

		LazyLoader.load('http://ebs.seriatv.com/seria/components/SERIA_VideoPlayer/js/easyXDM.min.js', function() {
			var scripts = document.getElementsByTagName('script');
			for(var i in SeriaPlayerClass.allReady) {
				if(typeof SeriaPlayerClass.elements[i] == 'string') 
					SeriaPlayerClass.allReady[i](document.getElementById(SeriaPlayerClass.elements[i]));
				else
					SeriaPlayerClass.allReady[i](SeriaPlayerClass.elements[i]);
			}

		});

	}
<?php
	if(!isset($_GET['element']))
		echo '
document.write(\'<div id="SeriaPlayer\' + SeriaPlayerClass.allIndex + \'" style="'."width: ".$_GET['width']."px; height: ".$_GET['height']."px;".'"></div><style type="text/css">#SeriaPlayer\' + SeriaPlayerClass.allIndex + \' iframe { width: 100%; height: 100%; }</style>\');
SeriaPlayerClass.elements[SeriaPlayerClass.allIndex] = "SeriaPlayer" + SeriaPlayerClass.allIndex;
';
	else { ?>
		var st = document.createElement('style');
		var text = '#<?php echo $_GET['element']; ?> iframe { width: 100%; height: 100%; }';
		st.setAttribute("type", "text/css");
		if(st.styleSheet) {
			st.styleSheet.cssText = text; // For IE
		} else {
			var textnode = document.createTextNode(text);
			st.innerHTML = text;
		}
		var head = document.getElementsByTagName("head")[0];
		if(head)
			head.appendChild(st);
		else
			document.body.appendChild(st);

		SeriaPlayerClass.elements[SeriaPlayerClass.allIndex] = document.getElementById(<?php echo json_encode($_GET['element']); ?>);
<?php } ?>
	SeriaPlayerClass.allIndex++;
}());
/*
setTimeout(function() {
	var ssb = document.createElement('script');
	ssb.src = 'http://ebs.seriatv.com/seria/components/SERIA_VideoPlayer/js/strobePlayerXDM.js.php?r=<?php echo rand(0, 999999);?>&iframeUrl=<?php echo rawurlencode($iframeUrl); ?>&containerId=<?php echo $containerId; ?>';
	ssb.type = 'text/javascript';
	SERIA_VideoPlayer.head.appendChild(ssb);
}, 1000);
*/

//	document.write("<script type=\"text/JavaScript\" src=\"http://ebs.seriatv.com/seria/components/SERIA_VideoPlayer/js/easyXDM.min.js\"><\/"+"script>");
//	document.write("<script type=\"text/JavaScript\" src=\"http://ebs.seriatv.com/seria/components/SERIA_VideoPlayer/js/strobePlayerXDM.js.php?r=5&iframeUrl=<?php echo $iframeUrl; ?>&containerId=<?php echo $containerId; ?>\"><\/"+"script>"); 

