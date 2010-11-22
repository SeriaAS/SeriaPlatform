SERIA PLATFORM COMMANDLINE OUTPUT
<?php
	if(isset($debugMessages) && sizeof($debugMessages) && SERIA_DEBUG)
	{
		echo "DEBUGMESSAGES:\n";
		foreach($debugMessages as $debugMessage)
			echo strip_tags(str_ireplace(array("<br>","<br/>","<br />"), array("\n","\n","\n"), str_pad(round($debugMessage['time'],4), 6, '0').": ".$debugMessage['message']."\n"));
		echo round(microtime(true)-$GLOBALS['seria']['microtime'],4).": FINISHED";
		echo "\n";
	}

	$messages = SERIA_HtmlFlash::getMessages();
	if(sizeof($messages)>0)
	{
		echo "MESSAGES:\n";
		foreach($messages as $class => $text)
		{
			echo strip_tags(str_ireplace(array("<br>","<br/>","<br />"), array("\n","\n","\n"), "- ".$text."\n"));
		}
		echo "\n";
	}
	echo strip_tags(str_ireplace(array("<br>","<br/>","<br />"), array("\n","\n","\n"), $contents));
	echo "\n";
