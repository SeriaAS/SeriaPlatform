<?php if (!defined('FROM_POPUP')) { die(); } ?>
<li class="fileIcon directoryIcon" id="fileIcon<?php echo $fileid; ?>">
	<?php
		$maxLength = 15;
		if (strlen($filename) > $maxLength) {
			$filename = substr($filename, 0, $maxLength);
			$filename .= '..';
		}
		$filename = wordwrap($filename, 16, ' ', true);
	?>
	
	<div>
		<img border="0" src="<?php echo SERIA_HTTP_ROOT . '/seria/filearchive/icons/directory.png'; ?>" alt="" width="32" height="32" />
	
		<span><?php echo $filename; ?></span>
	</div>
</li>