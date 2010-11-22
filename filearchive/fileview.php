<?php if (!defined('FROM_POPUP')) { die(); } ?>
<?php $elementPrefix = preg_replace('/[^a-z_0-9$]/i', '', $_GET['field']); ?>

<?php $filenameList = ''; ?>

<ul class="selectOrClickableIcons" <?php if ($multiselect) { ?>mnu="<?php echo _t('Use selected files'); ?>:javascript:returnSelectedFiles()" <?php } ?>>
	<?php foreach ($files as $key => $file) { ?>
		<?php if ($file->get('id')) { ?>
			
			<?php
				$file_id = $fileid = $file->get('id');
				$filename = $file->get('filename');
				
				$icon = getIconFromFilename($filename);
				$iconWidth = 32;
				$iconHeight = 32;
				
				$thumbnailUrl = '';
				try {
					$thumbnail = $file->getThumbnail(48, 48, array('transparent_fill'));
					list($thumbnailUrl, $iconWidth, $iconHeight) = $thumbnail;
				} catch (Exception $null) {
					$thumbnailUrl = '';
				}
				
				if ($thumbnailUrl) {
					$icon = $thumbnailUrl;
				}
				
				require(dirname(__FILE__) . '/' . 'fileicon.php');
				
				$filenameList .= ' filenameList[' . $file->get('id') . '] = ' . json_encode(htmlspecialchars($file->get('filename'))) . ';';
			?>
		<?php } ?>
	<?php } ?>
</ul>

<script type="text/javascript">
	<?php echo $filenameList; ?>
	
	<?php if ($multiselect) { ?>
		$('.selectOrClickableIcons').addClass('selectableIcons');
	<?php } else { ?>
		$('.selectOrClickableIcons').addClass('clickableIcons');
	<?php } ?>
</script>

<div style="clear: both; width: 100%"></div>