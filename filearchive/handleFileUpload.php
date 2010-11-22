<?php
	// Code for file upload
	
	if (sizeof($_FILES) && isset($_FILES['file'])) {
		$files = array();
		
		$fileData = array();
		foreach ($_FILES['file'] as $key => $values) {
			foreach ($values as $id => $value) {
				$fileData[$id][$key] = $value;
			}
		}
		
		foreach ($fileData as $fileassoc) {
			if ($fileassoc) {
				if ($fileassoc['tmp_name']) {
					$file = new SERIA_File($fileassoc['tmp_name'], $fileassoc['name']);
					if ($file->get('id') > 0) {
						$file->createArticle();
						$files[] = $file;
					} else {
						if (!$_GET['multiselect']) {
							echo '<script type="text/javascript">alert("' . _t('File upload failed') . '"); </script>';
						}
					}
		
					/*
					 * Save file under this namespace.
					 */
					if (isset($_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"])) {
						$file->setMeta('namespace', $_SESSION[SERIA_PREFIX."_FILES_NAMESPACE"]);
						if (!$_GET['multiselect']) {
							break;
						}
					}
				}
			}
		}
		
		if ($files) {
			$fileIds = array();
			foreach ($files as $file) {
				$fileIds[] = $file->get('id');
			}
			if ($_GET['multiselect']) {
				echo '<script type="text/javascript">
						$(function() {
							var returnValue = [' . json_encode($_GET['field']) . ', [' . implode(',', $fileIds) . ']];
							SERIA.Popup.returnValue(returnValue);
							window.close();
						});
					</script>';
			} else {
				echo '<script type="text/javascript">
						$(function() {
							var returnValue = {
								element: ' . json_encode($_GET['field']) . ',
								id: ' . json_encode(array_shift($fileIds)) . '
							};
							SERIA.Popup.returnValue(returnValue);
							window.close();
						});
					</script>';
			}
		}
	}
?>
