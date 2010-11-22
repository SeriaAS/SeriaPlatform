<?php if (!defined('FROM_POPUP')) { die(); } ?>
<?php $elementPrefix = preg_replace('/[^a-z0-9_$]/i', '', $_GET['field']); ?>
<p>
	<?php echo _t('Files uploaded via FTP or any other file transfer protocol to the configured incoming directory (defaults to incoming/) will appear here and will be available for usage. This is intended for large and/or multiple files upload.'); ?>
</p>
<script type="text/javascript">
	function loadFileView(path, ftpServerId) {
		var url = '<?php echo SERIA_HTTP_ROOT; ?>/seria/filearchive/incoming_view.php?path=' + escape(path) + '&ftp_server_id=' + ftpServerId;
		var id = '';
		if (ftpServerId) {
			id = ftpServerId;
		}
		$('#incoming_view' + id).load(url);
	}
	
	function openDirectory(path, ftpServer_id) {
		loadFileView(path, ftpServer_id);
	
		return false;
	}
	
	function copyFileFromIncoming(path, filename, ftpServer_id) {
		if (!ftpServer_id) {
			ftpServer_id = '';
		}
		var url = '<?php echo SERIA_HTTP_ROOT; ?>/seria/filearchive/incoming_copyfile.php?path=' + escape(path) + '&filename=' + escape(filename) + '&ftp_server_id=' + ftpServer_id;
		$.getJSON(url,
			function(data) {
				fileObject = {
					element: '<?php echo $elementPrefix; ?>',
					id: data.file_id,
					filename: data.filename
				}
				fileObject.preview = data.previewUrl;
				fileObject.previewWidth = data.previewWidth;
				fileObject.previewHeight = data.previewHeight;
				
				SERIA.Popup.returnValue(fileObject);
				window.close();
			});
	
		return false;
	}
	
	$(function() {
		<?php if ($incomingServer) { ?>
			loadFileView('/', <?php echo $incomingServer->id; ?>);
		<?php } else {?>
			loadFileView('/', 0);
		<?php } ?>
	});
</script>
<div id="incoming_view<?php if ($incomingServer) { echo $incomingServer->id; } ?>">
	<!-- LOAD INCOMING FILE VIEW INSIDE THIS DIV USING ASYNC JS -->
</div>