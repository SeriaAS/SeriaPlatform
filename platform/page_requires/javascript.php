<?php
return;
	/**
	*	Checks if the client supports javascript and stores that in a session variable
	*/

	if(isset($_SESSION[SERIA_PREFIX."_JAVASCRIPT"]))
	{ // javascript check has been performed
		if($_GET[SERIA_PREFIX."_JAVASCRIPT"])
		{
			$_SESSION[SERIA_PREFIX."_JAVASCRIPT"] = true;
		}
		else if(!$_SESSION[SERIA_PREFIX."_JAVASCRIPT"])
		{
			javascript_checker();
		}
	}
	else
	{ // perform check
		// assume no javascript support
		$_SESSION[SERIA_PREFIX."_JAVASCRIPT"] = false;

		javascript_checker();

	}

	function javascript_checker()
	{
		die("<html><head></head><body><h1>Javascript required</h1><script type='text/javascript'>
	var url = location.href;
alert(url);
	if(url.indexOf(\"?\")!=-1)
		url += \"&".SERIA_PREFIX."_JAVASCRIPT=1\";
	else
		url += \"?".SERIA_PREFIX."_JAVASCRIPT=1\";
alert(url);
	location.href=url;
</script></body></html>");
	}
