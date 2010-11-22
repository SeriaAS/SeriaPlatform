<?php if (!defined('FROM_POPUP')) { die(); } ?>
<?php if ($type == 'archive') {?>
	<div id="archive">
<?php } elseif ($type == 'recent') { ?>
	<div id="recent">
<?php } ?>

	<script type="text/javascript">
		function <?php echo $type; ?>_addNewFileUploader() {
			$('#<?php echo $type; ?>_file_uploaders br.breakBeforeLink').before('<br /><input type="file" name="file[]" id="file">');
		}
	</script>

	<form action="filearchive_popup.php#archive" method="get">
		<p class="fileSearchBox">
			<label for="search"><?php echo _t('Search:'); ?></label>
			<input type="text" name="search" id="search" value="<?php if (isset($_GET['search'])) echo htmlspecialchars($_GET['search']); ?>">
			
			<input type="hidden" name="field" value="<?php echo preg_replace('/[^a-z0-9_]/i', '', $_GET['field']); ?>">
			<input type="hidden" name="multiselect" value="<?php echo $_GET['multiselect']; ?>" />
			<?php if (isset($_GET['only_images'])) { ?>
				<input type="hidden" name="only_images" value="true">
			<?php } ?>
			<?php if (isset($_GET['only_videos'])) { ?>
				<input type="hidden" name="only_videos" value="true">
			<?php } ?>
					
			<input type="submit" value="<?php echo _t('Search'); ?>">
		</p>
	</form>
	<form action="" method="post" enctype="multipart/form-data">
		<p class="fileUploadBox">
			<label for="file"><?php echo _t('Upload file: '); ?></label>
			<div class="uploaderCollection" id="<?php echo $type; ?>_file_uploaders">
				<input type="file" name="file[]" id="file" />
				<?php if ($multiselect) { ?>
					<br class="breakBeforeLink" /><a href="" style="clear: both" onclick="<?php echo $type; ?>_addNewFileUploader(); return false"><?php echo _t('Upload another file'); ?></a>
				<?php } ?>
			</div>
			<input type="submit" value="<?php echo _t('Upload'); ?>" />
		</p>
	</form>