<script type="text/javascript">
	// Singleselect JS
	
	function useFile(fileId, filename, preview, width, height) {
		fileObject = {
			element: <?php echo json_encode($_GET['field']); ?>,
			id: fileId
		}
		
		SERIA.Popup.returnValue(fileObject);
		window.close();
		
		return false;
	}
	
	function getFileIdFromIcon(element) {
		var parts = element.attr('id').split('_');
		return parts[2];
	}
	
	function selectFile(element) {
		$('.clickableIcons li.singleSelected').removeClass('singleSelected');
	
		element.addClass('singleSelected');
		
		try {
			if (element.hasClass('incomingFileIcon')) {
				var filename = incomingFileList[element.attr('id')];
				var filesize = incomingFileSize[element.attr('id')];
				
				$('div#selectedFile div').load('<?php echo SERIA_HTTP_ROOT; ?>/seria/filearchive/selectedFileBox.php?filename=' + encodeURIComponent(filename) + '&filesize=' + encodeURIComponent(filesize));
				
			} else {
				fileId = getFileIdFromIcon(element);
				$('div#selectedFile div').load('<?php echo SERIA_HTTP_ROOT; ?>/seria/filearchive/selectedFileBox.php?file_id=' + fileId);
			}
		} catch (e) {}
	}
	
	function useFileClick() {
		element = $('.clickableIcons li.singleSelected');
		if (element.hasClass('incomingFileIcon')) {
			var filename = incomingFileList[element.attr('id')];
			var path = incomingPathList[element.attr('id')];
			var ftpServerId = incomingFtpServerList[element.attr('id')];
			
			copyFileFromIncoming(path, filename, ftpServerId);
		} else {
			useFile(getFileIdFromIcon(element));
		}
	}

	$('.clickableIcons li').bind('dblclick', function() {
		var fileId = getFileIdFromIcon($(this));
		useFile(fileId);
	});
	
	$('.clickableIcons li').bind('click', function() {
		var element = $(this);
		var fileId = getFileIdFromIcon(element);
		selectFile($(this));
	});
</script>