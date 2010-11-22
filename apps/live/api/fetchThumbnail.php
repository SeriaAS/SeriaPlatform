<?php
        header("Content-Type: image/jpeg");

	require_once(dirname(__FILE__).'/../../../main.php');
	
	$sec = intval($_GET['sec']);
        $filename = md5($_GET['rtmp']).'-'.(intval($sec/4)*4).'.jpg';

        $rtmp = $_GET['rtmp'];
        shell_exec("/usr/bin/rtmpdump -A ".$sec." -b ".($sec+1)." -r ".escapeshellarg($rtmp)." | /usr/local/bin/ffmpeg -i - -r 1 -f image2 ".SERIA_TMP_ROOT."/".$filename);
        header("Content-Type: image/jpeg");

        readfile(SERIA_TMP_ROOT.'/'.$filename);

