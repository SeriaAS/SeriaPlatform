<?php
	require_once(dirname(__FILE__).'/../../../../../main.php');

	$contents = '';
	if (isset($_POST['filename']) && isset($_POST['contents']) && isset($_POST['service'])) {
		$tmpfilename = tempnam(sys_get_temp_dir(), 'tfupl');
		$fh = fopen($tmpfilename, 'w');
		fwrite($fh, $_POST['contents']);
		fclose($fh);
		$file = new SERIA_File();
		$file->populateObjectFromFilePath($tmpfilename, $_POST['filename'], false, 'rename');
		$filexfer = new SERIA_RPCFileTransfer($_POST['service']);
		/*
		 * Ok start the carousel..
		 */
		ob_start();
		/* Upload the file */
		echo 'Original url: '.$file->get('url')."<br/>\n";
		$id = $filexfer->uploadFile($file);
		echo 'Uploaded to id: '.$id."<br/>\n";
		$rem_url = $filexfer->get($id, 'url');
		echo 'Uploaded to url: '.$rem_url."<br/>\n";
		$dfile = $filexfer->downloadFile($id);
		echo 'Downloaded to id: '.$dfile->get('id')."<br/>\n";
		echo 'Downloaded to url: '.$dfile->get('url')."<br/>\n";
		$contents = ob_get_clean();
	}
?><html>
	<head>
		<title>Test file transfer over RPC</title>
	</head>
	<body>
		<?php
			if ($contents) {
				echo $contents;
			} else {
				?>
					<form method='post'>
						<div>
							<label for='fname'>Filename: </label>
							<input id='fname' name='filename' type='text' %XHTML_CLOSE_TAG%>
						</div>
						<div>
							<label for='fc'>Contents: </label>
							<textarea id='fc' name='contents' cols='80' rows='25'></textarea>
						</div>
						<div>
							<label for='serv'>Talk to service: </label>
							<input id='serv' name='service' type='text' %XHTML_CLOSE_TAG%>
						</div>
						<div>
							<button type='submit'>Submit</button>
						</div>
					</form>
				<?php
			}
		?>
	</body>
</html>