<?php if (!defined('FROM_POPUP')) { die(); } ?>
<?php $elementPrefix = preg_replace('/[^a-z0-9_$]/i', '', $_GET['field']); ?>
<?php
	function selectFileClickHandler($file, $return = true) {
		$encodedFilename = $file->get('filename');
		$encodedFilename = htmlspecialchars($encodedFilename);
		$encodedFilename = addslashes($encodedFilename);
		$preview = '';
		try {
			list($preview, $width, $height) = $file->getThumbnail(48, 48);
		} catch (Exception $null) {
			$preview = '';
			$width = 0;
			$height = 0;
		}
		
		if (!$width) {
			$width = 0;
		}
		if (!$height) {
			$height = 0;
		}
		
		$line = 'selectFile(' . $file->get('id') . ', \'' . $encodedFilename . '\', \'' . $preview . '\', ' . $width . ', ' . $height . ');';
		
		if ($return) {
			return 'return ' . $line;
		} else {
			return $line;
		}
	}
?>

<div class="tabs">
	<ul>
		<li><a href="#recent"><span><?php echo _t('Recently uploaded'); ?></span></a></li>
		<li><a href="#archive"><span><?php echo _t('Search'); ?></span></a></li>
		<?php if ($incomingDirectoryEnabled) { ?>
			<li><a href="incoming_from_browser.php?field=<?php echo urlencode($_GET['field']); ?>" title="incoming"><span><?php echo _t('Incoming directory'); ?></span></a></li>
		<?php } ?>
		
		<?php foreach ($incomingServers as $server) { ?>
			<?php
				if (trim($server->title)) {
					$title = $server->title;
				} else {
					$title = $server->hostname;
				}
			?>
			<li><a href="incoming_from_browser.php?ftp_server_id=<?php echo $server->id; ?>&amp;field=<?php echo urlencode($_GET['field']); ?>" title="incoming<?php echo $server->id; ?>"><span><?php echo htmlspecialchars($title); ?></span></a></li>
		<?php } ?>
	</ul>
</div>
<div id="fileSelectMain">
	<div id="filePlane">
	