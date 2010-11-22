<?php
	require('../main.php');
	
	SERIA_Template::disable();

	if (SERIA_Base::isAdministrator() || !isset($_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"]) || !$_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"])
		SERIA_Base::pageRequires('admin'); /* NUPA special.. we can assume access to upload / create article when session var set */

	$file_id = $_GET['file_id'];
	$file = false;
	
	if ($file_id) {
		$pageCache = new SERIA_PageCache('fileinfobox_' . $file_id, 1800);
	}
	
	
	$notInCache = false;
	$cache = false;
	if ($file_id) {
		$cache = true;
		if ($pageCache->start()) {
			$file = SERIA_File::createObject($file_id);
			$notInCache = true;
		}
	} else {
		$notInCache = true;
		$cache = false;
	}

	if ($notInCache) {
		if ($file) {
			$filename = $file->get('filename');
			$filesize = $file->get('filesize');
		} else {
			$filename = '';
			$filesize = 0;
			
			if ($_GET['filename']) {
				$filename = $_GET['filename'];
			}
			if ($_GET['filesize']) {
				$filesize = $_GET['filesize'];
			}
		}
?>

	<p>
		<strong><?php echo _t('Filename: '); ?></strong><?php echo htmlspecialchars($filename); ?><br />
		<strong><?php echo _t('File size: '); ?></strong><?php echo SERIA_Format::filesize($filesize); ?><br />
		<?php if ($file) { ?>
			<strong><?php echo _t('Uploaded at: '); ?></strong><?php echo _date($file->get('createdAt')); ?><br />
			<?php if ($file->isImage()) { ?>
				<?php if ($imageWidth = $file->getMeta('image_width')) { ?>
					<strong><?php echo _t('Image size: '); ?></strong><?php echo $imageWidth; ?>x<?php echo $file->getMeta('image_height'); ?>
				<?php } ?>
			<?php } ?>
		<?php } ?>
	</p>
	<?php if ($file) { ?>
		<?php if ($file->isImage()) { ?>
			<?php
				list($url, $width, $height) = $file->getThumbnail(128, 128);
			?>
		
			<img src="<?php echo $url; ?>" width="<?php echo $width; ?>" height="<?php echo $height; ?>" alt="<?php echo str_replace('"', '&quot;', $file->get('filename')); ?>" />
		<?php } ?>
	<?php } ?>
	
<?php
	}
	if ($cache) {
		echo $pageCache->end();
	}
?>