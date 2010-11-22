<?php
	require_once(dirname(__FILE__).'/../../../../main.php');

	SERIA_Base::pageRequires('admin');

	$gui = new SERIA_Gui(_t('Test file transcoder'));
	$contents = '';

	class DEBUG_FilePowerpointSlidesTranscoder extends SERIA_FilePowerpointSlidesTranscoder
	{
		public function &getRecord()
		{
			return $this->record;
		}
	}
	if (sizeof($_POST)) {
		$file = SERIA_File::createObject($_POST['file_id']);
		/*
		 * Check if the files are transcoded..
		 */
		$relfiles = $file->getRelatedFiles('slidefrompresentation');
		if (!$relfiles && isset($_POST['normal']) && $_POST['normal']) {
			echo "Starting converter...<br>\n";
			$relfiles = $file->convertTo('ppt2png');
			if ($relfiles === false)
				die('Can\' convert.');
			if ($relfiles === true)
				$relfiles = false;
		}
		if ($relfiles) {
			ob_start();
			?>
			<div>
				<h1>Slides</h1>
			<?php
			foreach ($relfiles as $relfile)
			{
				?>
					<img src="<?php echo htmlspecialchars($relfile->get('url')); ?>" %XHTML_CLOSE_TAG%>
				<?php
			}
			?>
			</div>
			<?php
			$contents .= ob_get_clean();
		} else {
			/*
			 * Test file transcoding here:
			 */
			SERIA_Template::disable();
			if (isset($_POST['normal']) && $_POST['normal']) {
				echo 'Keep refreshing the page...';
				die();
			} else {
				set_time_limit(600);
				$transcoder = new DEBUG_FilePowerpointSlidesTranscoder();
				$transcoder->transcode($file);
				echo "Transcoder has started<br/>\n";
				while (!$transcoder->transcodeFromMaintain($file, array(), $transcoder->getRecord())) {
					echo "Transcoder has not completed\n";
					while (ob_end_flush());
					flush();
					sleep(1);
				}
				echo "Transcoder has completed\n";
				die();
			}
		}
	}

	SERIA_ScriptLoader::loadScript('platform-widgets');

	ob_start();
?>
<form method='post'>
	<div>
		<label for='fid'>File: </label><input id='fid' type='text' class='fileselect' name='file_id' %XHTML_CLOSE_TAG%>
	</div>
	<div>
		<!--  <label for='tra'>Transcoder:</label><input id='tra' type='text' name='transname' %XHTML_CLOSE_TAG%> -->
	</div>
	<div>
		<input type='checkbox' name='normal' value='1' id='normal_c' %XHTML_CLOSE_TAG%><label for='normal_c'> Use maintain-script.</label>
	</div>
	<div>
		<button type='submit'>Submit</button>
	</div>
</form>
<?php
	$contents .= ob_get_clean();
	$gui->contents($contents);
	
	echo $gui->output();
