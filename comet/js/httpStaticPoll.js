
if(typeof(SERIA)=="undefined") alert("Comet: common.js not included");
else if(typeof(SERIA.Timer)=="undefined") alert("Comet: Timer script is not loaded. Call SERIA_ScriptLoader::loadScript('Timer');");

SERIA.Comet = function (in_channelKey, in_subscriberKey, in_newMessageCallback, in_params)
{

	var comet_newMessageCallback;
	var comet_subscriberKey;
	var comet_channelKey;

	var comet_httpstaticpoll_pingUrl;
	var comet_httpstaticpoll_dataUrl;
	var comet_httpstaticpoll_receivedMessages = new Array();

	this.timeoutCall = function (obj)
	{
		obj.comet_httpstaticpoll_update();
		SERIA.Timer.setTimeout(obj.timeoutCall, 8000, obj);
	}
	this.timeoutPingCall = function (obj)
	{
		obj.comet_httpstaticpoll_ping();
		SERIA.Timer.setTimeout(obj.timeoutPingCall, 38000+Math.floor(Math.random()*4000), obj);
	}

	this.comet_init = function (channelKey, subscriberKey, newMessageCallback, params) {
		comet_newMessageCallback = newMessageCallback;
		comet_channelKey = channelKey;
		comet_subscriberKey = subscriberKey;
	
		comet_httpstaticpoll_pingUrl = params['pingUrl'];
		comet_httpstaticpoll_dataUrl = params['dataUrl'].replace('{KEY}', subscriberKey);
	
		SERIA.Timer.setTimeout(this.timeoutCall, 8000, this);
		SERIA.Timer.setTimeout(this.timeoutPingCall, 100000+Math.floor(Math.random()*4000), this);
		this.comet_httpstaticpoll_update();
	}

	this.comet_httpstaticpoll_update = function () {
		if(comet_httpstaticpoll_dataUrl.indexOf("?")==-1)
			var jsonURL = comet_httpstaticpoll_dataUrl + "?comet_rand=" + Math.random();
		else
			var jsonURL = comet_httpstaticpoll_dataUrl + "&comet_rand=" + Math.random();
		$.getJSON(jsonURL, function(messages, textStatus) {
			$.each(messages, function(key, message) {
				var id = message[0];
				var key = message[1];
				var content = message[2];
				var time = message[time];
				
				if (!comet_httpstaticpoll_receivedMessages[id]) {
					comet_httpstaticpoll_receivedMessages[id] = true;
				
					comet_newMessageCallback(key, content);
				}
			});
		});
	}
	this.comet_httpstaticpoll_ping = function () {
		$.getJSON(comet_httpstaticpoll_pingUrl,
		          {
		                  'subscriberKey': comet_subscriberKey,
		                  'randomValue': Math.random()
		          },
		          function (data) {
		          	if (data.error)
		          		alert('Error from server: ' + data.error);
		          }
		);
	}

	this.comet_init(in_channelKey, in_subscriberKey, in_newMessageCallback, in_params);
}

/* Backwards compat */
function comet_init(channelKey, subscriberKey, newMessageCallback, params) {
	new SERIA.Comet(channelKey, subscriberKey, newMessageCallback, params);
}
