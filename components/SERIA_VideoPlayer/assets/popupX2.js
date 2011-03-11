	if(!document.seriaVideoPlayer)
		document.seriaVideoPlayer = function(){}

	document.seriaVideoPlayer.currentDialog = false;

	document.seriaVideoPlayer.blockInteraction = false;

	function blockInteraction(on)
	{
		if(on && !document.seriaVideoPlayer.blockInteraction)
		{
			var wh;
 		        if(window.innerHeight)
                		wh = window.innerHeight;
        		else if(document.documentElement.clientHeight)
                		wh = window.document.documentElement.clientHeight;
        		else if(window.document.body)
                		wh = window.document.body.clientHeight;

			document.seriaVideoPlayer.blockInteraction = document.createElement('DIV');
			document.seriaVideoPlayer.blockInteraction.style.position='absolute';
			document.seriaVideoPlayer.blockInteraction.style.top = '0px';
			document.seriaVideoPlayer.blockInteraction.style.left = '0px';
			document.seriaVideoPlayer.blockInteraction.style.width = '100%';
			document.seriaVideoPlayer.blockInteraction.style.height = wh + 'px';
			document.seriaVideoPlayer.blockInteraction.style.backgroundColor = '#888888';
			document.seriaVideoPlayer.blockInteraction.style.opacity = 0.3;
			document.seriaVideoPlayer.blockInteraction.style.filter = 'alpha(opacity=30)';
			document.seriaVideoPlayer.blockInteraction.onclick = function() {
				document.seriaVideoPlayer.currentDialog.focus();
			}
			document.body.appendChild(document.seriaVideoPlayer.blockInteraction);
		}
		else if(!on && document.seriaVideoPlayer.blockInteraction)
		{
			document.body.removeChild(document.seriaVideoPlayer.blockInteraction);
			document.seriaVideoPlayer.blockInteraction = false;
			window.dialogParam = false;
		}
	}


	if(!window.showModalDialog) 
	{
		window.showModalDialog = function(sUrl, vArguments, sFeatures) {
			alert('Calling showModalDialog directly. This is a bug. Contact support.');
		}
	}

	function handleDialogClose()
	{
		try {
			if(document.seriaVideoPlayer.currentDialog && !document.seriaVideoPlayer.currentDialog.closed)
			{
				setTimeout(handleDialogClose, 50);
				return;
			}
			else if(window.dialogCallback)
			{
				window.dialogCallback(false);
			}

			if (window.focus) {
				window.focus();
			}
		} catch (err) {
			setTimeout(handleDialogClose, 50);
		}
	}


	function showPopupX(popupUrl, style, arg, callback, param)
	{
		showPopupCustomX(popupUrl, 950, 640, callback, param);
	}

	function showPopupCustomX(popupUrl, width, height, callback, param)
	{
		if(!callback)
		{
			alert('showPopupCustomX: no callback specified. This is a bug. Contact support.');
		}
		else if(false && document.seriaVideoPlayer.currentDialog)
		{
			alert('This window already has a dialog popup. This is a bug. Contact support.');
		}
		else
		{
			window.dialogCallback = function(returnValue)
			{

				blockInteraction(false);
				document.seriaVideoPlayer.currentDialog = false;
				window.dialogCallback = false;
				callback(returnValue);
			}
			blockInteraction(true);
			var windowName = 'modaldialog' + Math.round(10000000*Math.random());
                        if(width>screen.width) width=screen.width;
                        if(height>screen.height) height=screen.height;
			var left = screen.width / 2 - width / 2;
			var top = screen.height / 2 - height / 2;
			window.dialogParam = param;
			document.seriaVideoPlayer.currentDialog = window.open(popupUrl, windowName, "dependant=yes,directories=no,fullscreen=yes,toolbar=no,menubar=no,location=no,resizable=no,scrollbars=no,status=no,width="+width+",height="+height+",left="+left+",top="+top);
			if(!document.seriaVideoPlayer.currentDialog) {
				alert("A popup blocker prevents a window from being opened. This could be due to the web browser or another external program. Please turn this off for this site, or contact your system administrator for help. There may be multiple popup blockers, see support.seria.no/popup for more information.");
				blockInteraction(false);
				document.seriaVideoPlayer.currentDialog = false;
				window.dialogCallback = false;
			} else {
				handleDialogClose();
			}
		}
	}
