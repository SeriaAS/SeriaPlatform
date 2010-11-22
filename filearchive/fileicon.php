<?php if (!defined('FROM_POPUP')) { die(); } ?>

<li class="fileIcon<?php if ($extraClass) { echo ' ' . $extraClass; } ?>" id="fileIcon_<?php echo $type; ?>_<?php echo $fileid; ?>">
	<?php
		$extension = substr(strrchr($filename, '.'), 1, strlen($filename));
		
		$maxLength = 15;
		if (strlen($filename) > $maxLength) {
			$filename = substr($filename, 0, $maxLength - (strlen($extension) + 1));
			$filename .= '..' . $extension;
		}
		$filename = htmlspecialchars(wordwrap($filename, 16, ' ', true));
	?>
	<div>
		<img border="0" src="<?php echo $icon; ?>" alt="" width="<?php echo $iconWidth; ?>" height="<?php echo $iconHeight; ?>" />
	
		<span><?php echo $filename; ?></span>
	</div>
</li>