<?php
	function input($default=NULL, $prompt='')
	{
		echo $prompt;
		if($default!==NULL)
			echo "[".$default."] ";
		while(ob_end_flush());
		$string = fgets(STDIN, 4096);
		if($string=="")
			return $default;
		return trim($string);
	}

	function select(array $choices, $default=NULL, $prompt='')
	{
		while(true)
		{
			echo $prompt;
			$res = input($default);
			foreach($choices as $choice)
				if($res == $choice)
					return $res;
		}
	}

	function outputerr($message)
	{
		fwrite(STDOUT, $message);
	}

	function syntax_error($code, $arguments, $message)
	{
		global $argv;
		outputerr('Usage: '.$argv[0].' '.$arguments."\n$message\n");
		exit($code);
	}

	function runtime_error($code, $message)
	{
		outputerr('Runtime error: '.$message."\n");
		exit($code);
	}

	function minor_error($message)
	{
		outputerr("! ".$message."\n");
	}
