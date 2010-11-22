<?php
	SERIA_Template::cssInclude(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/style.css'));
?><script type='text/javascript'>
	<!--
		/*
		 * Capture select/unselect
		 */
		(function () {
			var superSelectFile = selectFile;
			var superUnselectFile = unselectFile;

			var fileids = new Array();
			var filenames = new Array();

			var updateTextBox = function ()
			{
				var obj = document.getElementById('openTheseFiles');

				if (fileids.length == 1) {
					obj.value = filenames[0];
				} else if (fileids.length > 0) {
					obj.value = '"'+filenames[0]+'"';
					for (var i = 1; i < filenames.length; i++) {
						obj.value += ' "'+filenames[i]+'"';
					}
				} else {
					obj.value = '';
				}
			}
			selectFile = function (id) {
				superSelectFile(id);
				var filename = getFilenameOfDisplayedFile(id);
				fileids[fileids.length] = id;
				filenames[filenames.length] = filename;
				updateTextBox();
			}
			unselectFile = function (id) {
				superUnselectFile(id);
				var filename = getFilenameOfDisplayedFile(id);
				var newIds = new Array();
				var newNames = new Array();
				for (var i = 0; i < fileids.length; i++) {
					if (fileids[i] != id) {
						newIds[newIds.length] = fileids[i];
						newNames[newNames.length] = filenames[i];
					}
				}
				fileids = newIds;
				filenames = newNames;
				updateTextBox();
			}
		})();

		function cancelFilearchive2(event)
		{
			window.close();
		}
		function submitFilearchive2(event)
		{
			var selectedFiles = getSelectedFiles();
			if (selectedFiles.length > 0) {
				SERIA.Popup.returnValue(selectedFiles);
			}
			return false;
		}
	-->
</script>
<div id='filearchive2_select_container'>
	<div id='filearchive2_select_display'>
		<!--[if lt IE 8]>
			<table border='0' cellspacing='0' cellpadding='0'><tr><td class='filearchive2_select_label'>
		<![endif]-->
		<label for='openTheseFiles'><?php echo htmlspecialchars(_t('Open file(s): ')); ?></label>
		<!--[if lt IE 8]>
			</td><td class='filearchive2_select_input'>
		<![endif]-->
		<input type='text' id='openTheseFiles' value='' %XHTML_CLOSE_TAG%>
		<!--[if lt IE 8]>
			</td><td class='filearchive2_select_div'>
		<![endif]-->
		<div>&nbsp;</div>
		<!--[if lt IE 8]>
			</td><td class='filearchive2_select_button'>
		<![endif]-->
		<button type='submit' onclick='return submitFilearchive2(event);'><?php echo htmlspecialchars(_t('Open')); ?></button>
		<!--[if lt IE 8]>
			</td><td class='filearchive2_select_button'>
		<![endif]-->
		<button type='button' onclick='cancelFilearchive2(event);'><?php echo htmlspecialchars(_t('Cancel')); ?></button>
		<!--[if lt IE 8]>
			</td></tr></table>
		<![endif]-->
	</div>
</div>
