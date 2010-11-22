<script type="text/javascript">
	// Multiselect JS
	
	function returnSelectedFiles() {
		var fileList = [];
		
		for (var key in selectedFiles) {
			if (selectedFiles[key]) {
				fileList.push(selectedFiles[key]);
			}
		}
		
		SERIA.Popup.returnValue([<?php echo json_encode($_GET['field']); ?>, fileList]);
		window.close();
		return false;
	}
		
	function setSelected(id) {
		selectedFiles[id] = id;
	}
	
	function removeSelected(id) {
		if (selectedFiles[id]) {
			selectedFiles[id] = undefined;
		}
	}
	
	function updateFileList() {
		var element = $('#selectedFileList');
		element.html('');
		for (var key in selectedFiles) {
			var id = selectedFiles[key];
			if (id) {
				var filename = filenameList[id];
				element.append('<li>' + filename + '</li>');
			}
		}
		
		var iconElements = $('ul.selectableIcons li.ui-selectee');
		iconElements.each(function() {
			var icon = $(this);
			var idparts = icon.attr('id').split('_');
			var id = idparts[2];
			
			var found = false;
			for (var key in selectedFiles) {
				if (selectedFiles[key] == id) {
					found = true;
				}
			}
			
			if (found) {
				if (!icon.hasClass('ui-selected')) {
					icon.addClass('ui-selected');
				}
			} else {
				if (icon.hasClass('ui-selected')) {
					icon.removeClass('ui-selected');
				}
			}
		});
	}
	
	function updateSelectedFiles() {
		var fileElements = $('ul.selectableIcons li.ui-selectee');
		
		var i = 0;
		fileElements.each(function() {
			var attrid = $(this).attr('id');
			var idParts = attrid.split('_');
			
			if ($(this).hasClass('ui-selected')) {
				setSelected(idParts[2]);
			} else {
				removeSelected(idParts[2]);
			}
		});
		
		updateFileList();
	}
	
	$(function() {
		$('.selectableIcons').selectable({
			start: function() {
				fileSelecting = true;
				
				updateSelectedFiles();
			},
			stop: function() {
				fileSelecting = false;
				
				updateSelectedFiles();
			}
		});
	});
	
	$(function() {
		var field = $(window.opener.document.getElementById(<?php echo json_encode($_GET['field']); ?>));
		var values = field.val().split(',');
		for (var key in values) {
			var value = values[key];
			setSelected(value);
		}
		updateFileList();
	});
</script>