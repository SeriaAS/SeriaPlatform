/**
*	This is the AJAX API for Seria Platform. It must be included AFTER including private.js or public.js, since it
*	relies on the global SERIA being available.
*/
if(typeof(SERIA_VARS)=='undefined') alert("SERIA_VARS have not been defined.");
if(typeof(SERIA)=='undefined') alert("Either private.js or public.js is not included. One of these must be included before including common.js");

/**
*	An object representing an article from Seria Platform
*/
SERIA.Lib = {
	/**
	*	Caches the results from a function call - for example an AJAX request. If the result is cached, the function is never called
	*/
	Cache : function(id, callback) {
		if(typeof(this.data)=="undefined") 
				this.data = new Object();

		if(this.data[id])
			return this.data[id];

		return this.data[id] = callback();
	}
	,
	/**
	*	Allows synchronized calls to the server. Example: var result = SERIA.Lib.SJSON("http://url/", { id:293 });
	*/
	SJSON : function(url, params) {
		var r = jQuery.ajax({
			"async" : false,
			"data" : params,
			"dataType" : "json",
			"timeout" : 3,
			"url" : url
		}).responseText;

		try {
			eval("r = " + r + ";");
		} catch (e) {
			return false;
		}

		if(r.error)
			throw r.error;

		return r;
	},
	
	/* Asynchronous JSON call */
	AJSON : function(url, params, callback) {
		jQuery.getJSON(url, params, callback);
	}
}

/**
*	Interface for working with articles. All functions require a numeric article id.
*/
SERIA.Article = {
	getAbstract : function(id) {
		return SERIA.Lib.Cache("article::getAbstract:" + id, function() {

			return SERIA.Lib.SJSON(SERIA_VARS.HTTP_CACHED_ROOT + "/seria/platform/json/article/getAbstract.php", {"id":id});

		});
	}
	,
	getArticle : function (id) {
		try
		{
			return SERIA.Lib.SJSON(SERIA_VARS.HTTP_CACHED_ROOT + "/seria/platform/json/article/getArticle.php", {
				"id":id
			});
		}
		catch (e)
		{
			alert("SERIA.Article.getArticle: " + e);
		}
	}
	,
	setField : function (id, fieldName, value) {
		try
		{
			return SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + "/seria/platform/json/article/setField.php", {
				"id":id,
				"fieldName":fieldName,
				"value":value
			});
		}
		catch (e)
		{
			alert("SERIA.Article.setField: " + e);
		}
	}
	,
	getField : function (id, fieldName) {
		try
		{
			return SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + "/seria/platform/json/article/getField.php", {
				"id":id,
				"fieldName":fieldName
			});
		}
		catch (e)
		{
			alert("SERIA.Article.getField: " + e);
		}
	}
	,
	sendTimingStatistics : function(id, caption, time) {
		response = SERIA.Lib.SJSON(SERIA_VARS.HTTP_ROOT + "/seria/platform/json/article/sendTimingStatistics.php", {
			"id":id,
			"caption":caption,
			"time":time
		});
		return response;
	}
}

/**
*	Popup window handling
*/

SERIA.Popup = {
	show : function(url, width, height, callback, style) {
	
		if(SERIA.Popup.currentPopup)
		{
			throw 'There is already a dialog window active.';
		}
		else
		{
			// defaults
			if(!width) width = 600;
			if(!height) height = 400;
			if(!style) style = "width=" + width + ", height=" + height + ", scrollbars=no, location=no, directories=no, status=no, menubar=no, copyhistory=no, help=no";
			else
			{
				if(style.indexOf('width') == -1)
					style += ",width=" + width;
				if(style.indexOf('height') == -1)
					style += ",height=" + height;
			}
			SERIA.Popup.currentCallback = callback;

			SERIA.Popup.currentPopup = window.open(url, 'dialog' + Math.ceil(Math.random()*5), style);
			// TODO: should block interaction here
			// Handle close by clicking X in the popup:
			SERIA.Popup._popupCheck();
		}

	}
	,
	amIAPopup : function() {
		if(top.opener && top.opener.SERIA && top.opener.SERIA.Popup.currentPopup)
		{
			return top.opener;
		}
	}
	,
	returnValue : function(value) {
		if(!SERIA.Popup.amIAPopup())
		{
			alert('This is not a popup window.');
		}
		else
		{
			top.opener.SERIA.Popup._returnValue(value);
		}
	}
	,
	// Special callback that must be called from a child window.
	_returnValue : function(value) {
		if(!SERIA.Popup.currentPopup)
		{
			throw 'There is no dialog active.';
		}
		else if(!SERIA.Popup.currentCallback)
		{
			SERIA.Popup.currentPopup = false;
			throw 'No callback defined, but was called from popup window.';
		}
		else
		{
			SERIA.Popup.currentPopup.close();
			SERIA.Popup.currentPopup = false;
			var callback = SERIA.Popup.currentCallback;
			SERIA.Popup.currentCallback = false;
			callback(value);
		}
	}
	,
	// Special callback that checks if the child window have been closed
	_popupCheck : function() {
		if(SERIA.Popup.currentPopup && !SERIA.Popup.currentPopup.closed)
		{
			setTimeout(SERIA.Popup._popupCheck, 50);
		}
		else
		{ // window was closed without callback
			if(SERIA.Popup.currentCallback)
			{
				var callback = SERIA.Popup.currentCallback;
				SERIA.Popup.currentPopup = false;
				SERIA.Popup.currentCallback = false;
				callback(false);
			}
		}
	}
}


