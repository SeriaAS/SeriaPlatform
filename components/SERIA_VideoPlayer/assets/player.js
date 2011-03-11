function VideoPlayer() {
	var self = this;			// a reference to this object for anonymous functions

	/**
	*	Information about the current video being played
	*/

	/**
	*	Get the duration of the current video in seconds. NaN means not available, Infinity means live stream.
	*/
	this.duration = NaN;

	/**
	*	Returns true if the video player is ready to be interfaced with using seek etc.
	*/
	this.isReady = false;

	this.fullscreen = function() {
		if(!self._isReady()) return;

		this._countSimpleEvent('fullscreen');

		try {
			if(self._video && self._video.webkitEnterFullscreen) {
				return self._video.webkitEnterFullscreen();
			}
		} catch (e) { return false; }

		var loc = location.href;
		if(loc.indexOf('#')!=-1)
			loc = loc.substring(0, loc.indexOf('#'));
		loc += "#";

		if(self._hasPlayed!==false)
			loc += "start=" + this.getCurrentTime() + "&";

		loc += "fullscreen=1&";

		var r = showPopupCustomX(loc, 9000, 9000, function(result) { self.pause(); }, this.getCurrentTime()); // , style, arg, callback, param)
		self.pause();
		if(document.seriaVideoPlayer.currentDialog) return true;
		return false;
	}

	this._parseParams = function() {
		var p = new Object();
		var h = location.href;
		if(h.indexOf('#')==-1)
			return p;
		h = h.substring(h.indexOf('#')+1);
		h = h.split('&');
		for(var i in h) if(h[i])
		{
			var parts = h[i].split('=');
			p[parts[0]] = parts[1];
		}
		return p;
	}

	/**
	*	Get the current playback position, in seconds. NaN means not available.
	*/
	this.getCurrentTime = function() {
		if(!self._isReady()) return NaN;
		if(self._video) return self._video.currentTime;
		else if(self._flash) return self._flash.getCurrentTime();
		else return NaN;
	}

	/**
	*	Rewind the video and be ready to start again. Stop the video and display the poster image.
	*/
	this.rewind = function() {
		this._isRewinding = true;
		this.seek(0);
		if(!this.isPaused())
			this.pause();
		if(self._flash)
			self._showImage();
	}

	this._showImage = function() {
		if(!self._isReady()) return;
		if(!this._flash) { alert('_showImage only for flash player'); return; }
		if(self.img) {
			self.img.style.display = 'block';
			self._flash.style.left = '0';
		}
	}

	this._hideImage = function() {
		if(!self._isReady()) return;
		if(!this._flash) { alert('_showPlayer only for flash player'); return; }
		if(self.img) {
			self.img.style.display = 'none';
			self._flash.style.left = '-10';
		}
	}

	/**
	*	Get the current volume level between 0 and 1. NaN means not available.
	*/
	this.getVolume = function() {
		if(!self._isReady()) return NaN;
		if(self._video) return self._video.volume;
		else if(self._flash) return self._flash.getVolume();
		else return NaN;
	}

	/**
	*	Set the volume level between 0 and 1.
	*/
	this.setVolume = function(volume) {
		if(!self._isReady()) return NaN;
		if(self._video) self._video.volume = volume;
		else if(self._flash) {
			try { // some unknown exception NPObject
				self._flash.setVolume(volume);
			} catch (e) {}
		}
	}

	/**
	*	Get the duration of the current video.
	*/
	this.getDuration = function() {
		if(!self._isReady()) return NaN;
		if(self._video) return self._video.duration;
		else if(self._flash) return self._flash.getDuration();
		else return NaN;
	}

	/**
	*	Return true if the video playback have completed
	*/
	this.isEnded = function() {
		if(!self._isReady()) return;
		if(isNaN(self.getDuration()) || isNaN(self.getCurrentTime())) return false;
		if(self.getDuration() <= self.getCurrentTime()) return true;
		return false;

//		if(self._video) return self._video.ended;
//		else if(self._flash) return self._flash.isEnded();
//		return null;
	}

	/**
	*	Return true if the video have been paused
	*/
	this.isPaused = function() {
		if(!self._isReady()) return;

		if(self._hasPlayed==false)
		{ // semantically the same as not playing
			return true;
		}

		if(self._video) return self._video.paused;
		else if(self._flash) return self._flash.isPaused();
	}

	/**
	*	Start playing the video. If it is already started, seek to the beginning of the video.
	*/
	this.play = function() {
		if(!self._isReady()) return;

		if(!this._hasPlayed)
			this._countSimpleEvent('play');

		if(self._video) {
			this._hasPlayed = true;
			return self._video.play();
		}
		else if(self._flash) {
			this._hideImage();
			this._hasPlayed = true;
			return self._flash.playVideo();
		}

	}

	/**
	*	Try to find out if the player will be able to play. If it returns false, it cannot play - but if it returns true then it most likely can play.
	*/
	this.canPlay = function() {
		if(!self._isReady()) return;
		if(this._hasPlayed) return true;

		if(self._flash)
		{ // can play mp4, flv and rtmp-streams
			for(var i in window.videoData.sources)
			{ // assume all rtmp streams can be played, all files with http/https and extension .mp4, .f4v or .flv can be played.
				if(window.videoData.sources[i].src) {
					if(window.videoData.sources[i].src.indexOf('rtmp://')==0) return true;
					if(window.videoData.sources[i].src.indexOf('rtmpt://')==0) return true;
					if(window.videoData.sources[i].src.indexOf('rtmps://')==0) return true;
					if(window.videoData.sources[i].src.indexOf('http://')==0 || window.videoData.sources[i].src.indexOf('https://')==0)
					{
						if(window.videoData.sources[i].src.indexOf('.mp4')>0) return true;
						if(window.videoData.sources[i].src.indexOf('.flv')>0) return true;
						if(window.videoData.sources[i].src.indexOf('.f4v')>0) return true;
					}
				}
			}
		}
		else if(self._video)
		{ // use html 5 check to see if it supports the video
			for(var i in window.videoData.sources)
			{ // assume only http and https-streams can be played
				if(window.videoData.sources[i].src && window.videoData.sources[i].src.indexOf('http://')==0 || window.videoData.sources[i].src.indexOf('https://')==0)
				{ // protocol OK, probably can
					var info = self._video.canPlayType(window.videoData.sources[i].type);
					if(info!='' && info!='no') return true;
				}
			}
		}
		return false;
	}

	/**
	*	Pause video playback. Calling it again will resume playback.
	*/
	this.pause = function() {
		if(!self._isReady()) return;
		if(self._video)
			self._video.pause();
		else if(self._flash)
			self._flash.pause();	
	}

	/**
	*	INTERNAL
	*/
	this._flash = false;			// if using flash player, a reference to the flash dom object
	this._video = false;			// if using html 5 video player, a reference to the video dom object
	this._jQueryLoaded = false;		// if jQuery was loaded on demand this is true
	this._hasPlayed = false;		// if play have been called, this is true
	/**
	*	Call self to check if everything is ready prior to performing other actions on the videoPlayer object.
	*	Returns false if not ready.
	*/
	this._isReady = function() {
		if(!self.isReady)
			alert('Please check videoPlayer.isReady. I was not ready!!! :-p');
		return self.isReady;
	}

	this._resize = function() {
		var img = self.img;
		if(!img.originalWidth)
		{
			img.originalWidth = img.width;
			img.originalHeight = img.height;
		}
		img.style.zIndex = 0;
		var w = window.innerWidth;
		var h = window.innerHeight;
		if(!w)
		{
			if(document.documentElement && document.documentElement.clientWidth && document.documentElement.clientWidth!=0)
			{
				w = document.documentElement.clientWidth;
				h = document.documentElement.clientHeight;
			}
			else if(document.body && document.body.clientWidth)
			{
				w = document.body.clientWidth;
				h = document.body.clientHeight;
			}
		}
		if(img.originalWidth / w > img.originalHeight / h)
		{
			var nh = img.originalHeight * (w / img.originalWidth);
			img.style.height = 'auto';
			img.style.width = w + "px";
			img.style.top = ((h - nh) / 2) + "px";
			img.style.left = '0px';
		}
		else
		{
			var nw = img.originalWidth * (h / img.originalHeight);
			img.style.width = 'auto';
			img.style.height = h + "px";
			img.style.top = '0px';
			img.style.left = ((w - nw) / 2) + "px";
		}
	}

	this._ready = function() {
		var params = this._parseParams();

		self.isReady = true;
		if(self._flash) { // create an img tag to simulate poster
			if(!params.fullscreen) // already counted
				this._countSimpleEvent('flash');
			if(window.videoData.poster) {
				var img = document.createElement('img');
				self.img = img;
				img.src=window.videoData.poster;
				img.style.position='absolute';
				self._resize();
				var b = document.getElementsByTagName('body')[0];
				b.insertBefore(img, b.firstChild);
				img.onload = function() {
					setInterval(self._resize, 500);
				}
			}
		}
		else 
		{
			if(!params.fullscreen)
				this._countSimpleEvent('html5');
		}

		if(params.start) 
		{
			var start; 
			this.play();
			start = function(){
				try {
					self.seek(params.start);
				} catch (e) {
					setTimeout(start, 100);
				}
			};
			start();
		}
	}

	this.seek = function(newCurrentTime) {
		if(self._flash)
		{
			self._flash.setCurrentTime(newCurrentTime);
		}
		else if(self._video)
			self._video.currentTime = newCurrentTime;
	}

	this._useFailed = function() {
		this._countSimpleEvent('video-failed');
		alert('Video playback not supported in your browser');
	}

	this._useVideo = function() {
		var video = document.getElementById('video');
		if(video.canPlayType)
		{
			self._video = video;
			self._ready();
		}
		else self._useFailed();
	}
	this._useFlash = function(o) {
		self._flash = o;
		self._ready();
	}

	this._listen = function(object, event, callback) {
		if(object.addEventListener)
			return object.addEventListener(event, callback, false);
		else if(object.attachEvent)
			return object.attachEvent('on' + event, callback);
		else throw 'Event listening not supported in your browser';
	}

	this._listen(window, 'load', function() {

		if(!FlashDetect.installed)
		{
			self._useVideo();
			return;
		}

		var ieflash = document.getElementById('ieflash');
		var nieflash = document.getElementById('nieflash');
		var video = document.getElementById('video');
		var waiter = function() {
			if(ieflash && ieflash.isReady && ieflash.isReady())
			{
				self._useFlash(ieflash);
			}
			else if(nieflash && nieflash.isReady && nieflash.isReady())
			{
				self._useFlash(nieflash);
			}
			else
				setTimeout(waiter, 20);
		}
		waiter();
	});

	this._loadScript = function(url) {
		var s = document.createElement('script');
		s.type = 'text/javascript';
		s.src = url;
		s.onload = alert;
		document.getElementsByTagName('head')[0].appendChild(s);
	}

	this._countSimpleEvent = function() {
		var args = new Array();
		for(i = 0; i < arguments.length; i++)
			args[i] = arguments[i];
		return S.rpc('SERIA_VideoPlayer', 'countSimpleEvent')(window.videoData.objectKey, args.join(','))();
	};
}

window.videoPlayer = new VideoPlayer();
