/**
*       Javascript include for Seria Platform
*/

if(typeof(SERIA_VARS)!='undefined') { /* compat<3 */
	if(!SERIA_VARS.tmpEscInRow)
		SERIA_VARS.tmpEscInRow = 0;

	function seriaCaptureEsc(e)
	{
			if (typeof(event) == 'undefined')
				event = e;
			else if (!event)
				event = e;

			if(event.keyCode==27) SERIA_VARS.tmpEscInRow++;
			else SERIA_VARS.tmpEscInRow=0;

			if(SERIA_VARS.tmpEscInRow==2)
			{
					SERIA_VARS.tmpEscInRow = -1;
			var url = SERIA_VARS.HTTP_ROOT + '/seria/platform/pages/login.php?continue='+escape(SERIA_VARS.HTTP_ROOT + "/seria/#" + top.location.href);
					top.location.href = url;
			return false;
			}
	}
	if(document.addEventListener)
		document.addEventListener("keypress", seriaCaptureEsc, false);
	else if(document.attachEvent)
		document.attachEvent("onkeypress", seriaCaptureEsc);
	else
		document.onkeypress = seriaCaptureEsc;
}

var SERIA = {
	mode : "public"
}	
