<?php
	require_once(dirname(__FILE__)."/../../../main.php");

		if(SERIA_Base::isLoggedIn()) {
			$curr_token = intval(SERIA_Base::getParam('token'));
		
			$streamVars = array(
				'key' => md5(($curr_token+1).STREAM_API_SERIALIVE_KEY),
				'host' => STREAM_API_SERIALIVE_DOMAIN,
				'token' => ($curr_token+1),
				'streamName' => $_POST["streamName"]
			);
			SERIA_Base::setParam('token', intval($curr_token+1));
		
			$readfile = fopen(STREAM_API_SERIALIVE_URL."/download.php?key=".$streamVars['key']."&host=".$streamVars['host']."&token=".$streamVars['token']."&streamName=".$streamVars['streamName'], 'rb');
			// Kan i teorien om logga inn post'e streamName til /etc/whatever, men vil bare kunne skrive hvis access?
			$writefile = fopen(dirname(__FILE__)."/../../../../content/".$streamVars['streamName'].".flv", 'x');
			while(!feof($readfile)) {
				$line = fgets($readfile, 1024);
				fwrite($writefile, $line);
			}
			fclose($writefile);
			fclose($readfile);
			echo "SUCCESS";
		} else {
			die("NO ACCESS");
		}
