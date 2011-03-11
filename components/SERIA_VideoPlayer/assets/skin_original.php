<script src='<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/popupX2.js'></script>
<style type='text/css'>
	body { font-family: Arial, sans-serif;} 
	.duration {
		font-size: 13px;
		font-weight: bold;
	}
	.button {
		cursor: pointer;
	}
</style>
<div id='opacity'>
<div id='volumeBarWrap' style='right: 64px; bottom: 40px; width: 38px; height: 124px; cursor:pointer; position: absolute; overflow: hidden; background-color: #000; display:none;'>frode
	<div id='volumeBar' style='background-color: #000; position: absolute; top: 6px; left: 6px; right: 6px; bottom: 6px;-moz-border-radius: 8px; -khtml-border-radius: 8px; -webkit-border-radius: 8px; border-radius: 8px; border: 3px solid white; overflow: hidden;'>
		<div id='volume' style='bottom: 4px; left: 4px; right:4px;; height: 40px; -moz-border-radius: 3px; -khtml-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; position: absolute; background-color: #fff; overflow:hidden;'></div>
	</div>
</div>

<div id='toolbar' style='display:none;position: absolute; bottom: 0px; width: 100%; background-color: black;height:1px;'>
	<div style='position:absolute;'>
		<div id='progressBar' style='cursor:pointer;-moz-border-radius: 8px; -khtml-border-radius: 8px; -webkit-border-radius: 8px; border-radius: 8px; position: relative; top: 7px; height: 20px; left: 48px; width: 10px; height:20px; border: 3px solid white; overflow: hidden;'>
			<div id='durationOuter' class='duration' style='position: absolute; top: 4px; left: 534px; height: 20px; color: #fff; width: 120px; text-align:right;'>0:00 / 0:00</div>
			<div id='progress' style='-moz-border-radius: 3px; -khtml-border-radius: 3px; -webkit-border-radius: 3px; border-radius: 3px; position: absolute; top: 4px; left: 4px; bottom: 4px; width: 1px; background-color: #fff; overflow:hidden;'>
				<div id='durationInner' class='duration' style='position: absolute; left: 530px; top: 0px; height: 20px; color: #000; width: 120px; text-align:right;'>0:00 / 0:00</div>
			</div>
		</div>
	</div>
	<div style='position:absolute;'>
	</div>
</div>
</div>
<script type='text/javascript'>

        function icon(number, left, onclick, onhover, onout) {
                var e = document.createElement('div');
		var offsets = [ 	-20,	-69, 	-109, 	-154, 	-205, 	-264];
		var widths = [ 		35, 	30, 	32, 	34, 	45, 	47 ];
		e.style.position = 'absolute';
                e.style.width = widths[number] + 'px';
                e.style.height = '40px';
		if(left<0)
		{
			left *= -1;
			e.style.right = left + 'px';
		}
		else
			e.style.left = left + 'px';
		e.style.top = '0px';
		e.style.backgroundPosition = (offsets[number]) + 'px -21px';
                e.style.backgroundImage = 'url(<?php echo SERIA_HTTP_ROOT; ?>/seria/components/SERIA_VideoPlayer/assets/SERIA_VideoPlayerButtons.png)';
		e.className = 'button';
		if(onclick)
			jQuery(e).click(onclick);
		if(onhover && onout)
			jQuery(e).hover(onhover, onout);
                return e;
        }

	var toolbarVisible = false;
	var tmp = new Date();
	var lastMovement = tmp.getTime();
	var lastToggle = tmp.getTime();

	var wasMutedFrom = false;

        jQuery(function(){
		var pauseB;
		var playB;
		var fullscreenB;


		setInterval(function() {
			/* SHOW AND HIDE THE TOOLBAR */
			tmp = new Date();
			if(lastMovement < tmp.getTime()-7500)
			{
				toolbarVisible = false;
				jQuery('#toolbar').stop(true,false).animate({height:'1px'}, 400);
			}

			/* IF THE VIDEO ENDS, REWIND. SHOULD POSSIBLY BE BOUND TO SOME PLAYLIST LOGIC */
			if(window.videoPlayer.isReady && window.videoPlayer.isEnded())
			{
				window.videoPlayer.rewind();
			}

			/* IF THE VIDEO IS PAUSED SOMEHOW, UPDATE THE PAUSE BUTTON */
			if(window.videoPlayer.isReady && window.videoPlayer.isPaused())
			{
				playB.style.display = 'block';
				pauseB.style.display = 'none';
			}
			else
			{
				playB.style.display = 'none';
				pauseB.style.display = 'block';
			}
		}, 100);

		var body = document.getElementsByTagName('body')[0];

		jQuery(body).bind('touchstart', function() {
			if(toolbarVisible == false)
			{
				toolbarVisible = true;
				jQuery('#toolbar').stop(true,false).animate({height:'40px'}, 400);
			}
			tmp = new Date();
			lastMovement = tmp.getTime();
		});

		jQuery('body').hover(function() {
			toolbarVisible = true;
			jQuery('#toolbar').stop(true,false).animate({height:'40px'}, 400);
		}, function() {
//			toolbarVisible = false;
//			jQuery('#toolbar').stop(true,false).animate({height:'1px'}, 400);
		}).mousemove(function(){
			if(toolbarVisible == false)
			{
				toolbarVisible = true;
				jQuery('#toolbar').stop(true,false).animate({height:'40px'}, 400);
				jQuery('#volumeBarWrap').css({display:'none'});
			}
			tmp = new Date();
			lastMovement = tmp.getTime();
		});

		window.cmdResize = function() {
			var pb = document.getElementById('progressBar');
			var duO = document.getElementById('durationOuter');
			var duI = document.getElementById('durationInner');
			var w = jQuery('body').width()-150;

			jQuery(pb).css({width: (w-14) + 'px'});
			jQuery(duO).css({left: (w-150) + 'px'});
			jQuery(duI).css({left: (w-154) + 'px'});
		}

		window.cmdPause = function() {
			if(!window.videoPlayer || !window.videoPlayer.isReady) return;
			window.videoPlayer.pause();
			playB.style.display = 'block';
			pauseB.style.display = 'none';
		}

		window.cmdPlay = function() {
			if(!window.videoPlayer || !window.videoPlayer.isReady) return;
			toolbarVisible = true;
			jQuery('#toolbar').stop(true,false).animate({height:'40px'}, 400);
			tmp = new Date();
			lastMovement = tmp.getTime();
			if(window.videoPlayer.isPaused())
			{
				window.videoPlayer.play();
			}
			else
			{
				window.videoPlayer.pause();
			}
			playB.style.display = 'none';
			pauseB.style.display = 'block';
			return false;
		}

		window.cmdFullscreen = function() {
			if(!window.videoPlayer || !window.videoPlayer.isReady) return;
			if(window.videoPlayer.isPaused()) return;
			if(!window.videoPlayer.fullscreen())
				alert('Fullscreen not possible on your device');
		}

		window.cmdVolume = function() {
			if(wasMutedFrom!==false)
			{
				videoPlayer.setVolume(wasMutedFrom);
				wasMutedFrom = false;
			}
			else
			{
				wasMutedFrom = videoPlayer.getVolume();
				videoPlayer.setVolume(0);
			}
			setTimeout(window.calcVolume,0);
		}

		window.enterVolume = function() {
			jQuery('#volumeBarWrap').css({display:'block'});
			window.calcVolume();
		}

		window.exitVolume = function() {
			jQuery('#volumeBarWrap').css({display:'none'});
		}

		window.calcVolume = function(direct) {
			var v = window.videoPlayer.getVolume();
			var h = (v/1) * (jQuery('#volumeBar').height()-6);
			if(isNaN(h)) return;
			if(direct)
				jQuery('#volume').stop(true,false).css({height: h + 'px'});
			else
				jQuery('#volume').stop(true,false).animate({height: h + 'px'}, 250);
		}

		pauseB = icon(2, 5, window.cmdPause);
		pauseB.style.display = 'none';
		playB = icon(1, 5, window.cmdPlay);
		fullscreenB = icon(4, -10, window.cmdFullscreen);
		volumeB = icon(3, -67, window.cmdVolume, window.enterVolume, window.exitVolume);

		jQuery('#volumeBarWrap').hover(window.enterVolume,window.exitVolume);
		jQuery('#ieflash,#nieflash,video').click(window.cmdPlay); //, window.cmdPause);

		jQuery('#opacity').css({opacity: 0.5});

		jQuery('#toolbar,#volumeBarWrap').hover(function(){
			jQuery('#opacity').stop(true,false).animate({opacity: 0.8},100);
		},function(){
			jQuery('#opacity').stop(true,false).animate({opacity: 0.5},100);
		});
		jQuery('#toolbar').append(playB).append(pauseB).append(fullscreenB).append(volumeB);

		jQuery('#volumeBar').mousedown(function(e) {
			if(!window.videoPlayer || !window.videoPlayer.isReady) return;

			var element = this;

			var follow = function(ev) {
				var h = parseInt(jQuery(element).css('height'));
				var y = ev.pageY - element.offsetTop - element.parentElement.offsetTop;
				var pst = y/h;
				if(pst>1) pst = 1;
				else if(pst<0) pst = 0;
				pst = 1 - pst;
				window.videoPlayer.setVolume(pst);
				window.calcVolume(true);
				wasMutedFrom = false;
			}
			follow(e);
			var up;
			up = function(ev){
				jQuery('#volumeBar').unbind('mousemove');
				jQuery('#volumeBar').unbind('mouseup');
			}
			jQuery('#volumeBar').bind('mousemove', follow);
			jQuery(document.getElementsByTagName('body')[0]).bind('mouseup', up);
		});

		jQuery('#progressBar').mousedown(function(e) {
			if(!window.videoPlayer || !window.videoPlayer.isReady) return;
			var w = parseInt(jQuery(this).css('width'));
			var x = e.pageX - this.offsetLeft;
			var pst = x/w;
			if(pst>1) pst = 1;
			else if(pst<0) pst = 0;
			window.videoPlayer.seek(videoPlayer.getDuration() * pst);
		});
		setInterval(function(){
			if(!window.videoPlayer || !window.videoPlayer.isReady) return;

			var d = window.videoPlayer.getDuration();
			if(isNaN(d)) return;
			var c = window.videoPlayer.getCurrentTime();
			if(isNaN(c)) return;

			var w = (c/d) * (jQuery('#progressBar').width()-8);

			if(!isNaN(w))
				jQuery('#progress').stop(true,false).animate({width: w + 'px'},250);

			if(d) jQuery('.duration').html(timeRender(c) + " / " + timeRender(d));
		}, 200);
		window.cmdResize();
		jQuery(window).resize(window.cmdResize);
		// wait for videoplayer
		var waitForPlayer;
		waitForPlayer = function() {
			if(window.videoPlayer && window.videoPlayer.isReady)
				jQuery('#toolbar').css({display:'block'});
			else
				setTimeout(waitForPlayer, 100);
		}
		setTimeout(waitForPlayer, 100);
        });

	function timeRender(seconds) {
		var minutes = Math.floor(seconds/60);
		var seconds = Math.floor(seconds%60);
		if(seconds<10) seconds = "0" + seconds;
		return minutes + ":" + seconds;
	}
</script>
