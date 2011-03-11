if(typeof(SERIA_VARS) == 'undefined') alert('SERIA.js LOADED BEFORE SERIA_VARS WERE DEFINED!');
function S(arg) {
	if(this!==window)
	{ // constructing an object
		switch(typeof arg) {
			case 'string' : // assume first argument is httpRoot
				this.httpRoot = httpRoot;
				break;
		}
	}
	else
	{ // shortcut mode

	}
}
S.toString = function() {
	return 'SERIA PLATFORM JS INTERFACE';
}
S.httpRoot = SERIA_VARS.HTTP_ROOT;
S.prototype = S;
/**
*	Example:	S.ajax(url, [ settings ])(onSuccess, onFailure);
*/
S.ajax = function(method, url) {
	return function(onSuccess, onFailure) {
		var xhr = new S.xmlHttpRequest();
		xhr.open(method, url, true);
		xhr.onreadystatechange = function() {
			switch(xhr.readyState) {
				case 4:
					switch(xhr.status) {
						case 200 : 
							if(typeof(onSuccess)!='undefined') onSuccess(xhr.responseText);
							break;
						default : if(typeof(onFailure)!='undefined') onFailure(xhr.responseText, xhr.status);
							break;
					}
					break;
			}
		}
		xhr.send(null);
	}
}

/**
*	Example 1: 	S.rpc('SERIA_VideoPlayer','sendEvent')(argument1,argument2,argument3)(onSuccess,onFailure);
*	Example 2: 	this.sendEvent = S.rpc('SERIA_VideoPlayer', 'sendEvent');
*			this.sendEvent(argument1,argument2,argument3)(onSuccess,onFailure);
*	Example 3:	this.sendPlayEvent = this.sendEvent('play');
*			this.sendPlayEvent(onSuccess, onFailure);
*/
S.rpc = function(classname,method) {
	var u = this.httpRoot + '/seria/platform/rpc/v2.php?_r=' + Math.random() + '&e=json&c=' + encodeURIComponent(classname) + '&m=' + encodeURIComponent(method);
	return function() {
		if(arguments.length>0)
		{
			for(var i = 0; i < arguments.length; i++)
				u += '&'+i+'='+encodeURIComponent(arguments[i]);
		}
		return function(onSuccess,onFailure) {
			return S.ajax('GET', u)(function(json){
				var data = null;
				try {
					data = eval('(' + json + ')');
				} catch (e) {
					alert('Non-JSON encoded data from server: ' + json);
					return;
				}

				if(data && typeof(data) == 'object' && typeof(data.error) != 'undefined')
				{
					if(typeof(onFailure)!='undefined') onFailure(data, 200);
				}
				else
				{
					if(typeof(onSuccess)!='undefined') onSuccess(eval('(' + json + ')'));
				}
			}, function(json,code){
				try {
					var data = eval('(' + json + ')');
				} catch (e) {}
				if(typeof(onFailure)!='undefined') onFailure(data, code);

			});
		}
	}
}

/**
*	Lower level functions for internal use
*/

/**
*	Get an xmlHttpRequest object cross browser
*/
S.xmlHttpRequest = function() {
	if(XMLHttpRequest)
		return new XMLHttpRequest;
	else if(window.ActiveXObject)
		return new ActiveXObject('Microsoft.XMLHTTP');
}
