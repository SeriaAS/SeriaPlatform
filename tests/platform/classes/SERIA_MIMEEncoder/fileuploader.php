<?php

require_once(dirname(__FILE__).'/../../../../main.php');

$contents = '';

if (sizeof($_FILES) && isset($_POST['message'])) {
	ob_start();
	print_r($_FILES);
	print_r($_POST);
	$contents = ob_get_clean();
} else if (isset($_POST['filename']) && isset($_POST['filecont'])) {
	$tmpfilename = tempnam(sys_get_temp_dir(), 'tfupl');
	$fh = fopen($tmpfilename, 'w');
	fwrite($fh, $_POST['filecont']);
	fclose($fh);
	$file = new SERIA_File();
	$file->populateObjectFromFilePath($tmpfilename, $_POST['filename'], false, 'rename');
	if (isset($_POST['message']) && isset($_POST['recreate']) && $_POST['recreate']) {
		$browser = new SERIA_WebBrowser();
		$browser->navigateTo(SERIA_HTTP_ROOT.'/seria/tests/platform/classes/SERIA_MIMEEncoder/fileuploader.php', 'post');
		$browser->postFile('uploadTest', $file);
		$browser->postField('message', $_POST['message']);
		$res = $browser->fetchAll();
		if ($browser->responseCode == 200) {
			echo $res.'<!--SIGNATURE-->';
			die();
		} else
			$contents = $browser->responseCode.': '.$res;
	} else {
		$files = array(
			'testfile' => $file
		);
		$uploadData = SERIA_MIMEEncoder::createMultipartPost($_POST, $files);
		$contents = nl2br(htmlspecialchars(str_replace("\r\n", "\n", $uploadData)));
	}
} else if (isset($_POST['message']))
	$contents .= 'Message='.$_POST['message'];

?><html>
	<head>
		<title>File create and upload</title>
	</head>
	<body>
		<?php
		if ($contents)
			echo $contents;
		else {
			?>
		<form method='post'>
			<div>
				<label for='fname'>Filename:</label>
				<input id='fname' type='text' name='filename' value='' %XHTML_CLOSE_TAG%>
			</div>
			<div>
				<label for='fc'>Contents:</label>
				<textarea name='filecont'></textarea>
			</div>
			<div>
				<label for='msg'>Message:</label>
				<input id='msg' type='text' name='message' value='' %XHTML_CLOSE_TAG%>
			</div>
			<div>
				<input id='recreater' name='recreate' type='checkbox' value='1' %XHTML_CLOSE_TAG%>
				<label for='recreater'>Recreate object through recursive post (Ultimate test).</label>
			</div>
			<div>
				<button type='submit'>Create!</button>
			</div>
		</form>
			<?php
		}
		?>
		<div>
			<h2>DEBUGGER:</h2>
			<div>
				<span>POST:</span>
				<pre><?php ob_start(); print_r($_POST); echo htmlspecialchars(ob_get_clean()); ?></pre>
			</div>
			<div>
				<span>GET:</span>
				<pre><?php ob_start(); print_r($_GET); echo htmlspecialchars(ob_get_clean()); ?></pre>
			</div>
			<div>
				<span>FILES:</span>
				<pre><?php ob_start(); print_r($_FILES); echo htmlspecialchars(ob_get_clean()); ?></pre>
			</div>
		</div>
	</body>
</html>