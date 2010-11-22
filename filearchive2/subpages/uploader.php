<?php

require_once(dirname(__FILE__).'/../../main.php');

$uploaded = false;
$error = false;
if (isset($_FILES['uploader']) && $_FILES['uploader']) {
	$error = SERIA_IsInvalid::uploadedFile($_FILES['uploader'], true);
	if ($error === false) {
		/* Handle the file */
		try {
			$fileobj = new SERIA_File($_FILES['uploader']['tmp_name'], $_FILES['uploader']['name']);
			$fileobj->createArticle($_FILES['uploader']['name']);
			$filearticle = $fileobj->getArticle();
			$uploaded = $filearticle->get('id');
		} catch (Exception $e) {
			$error = $e->getMessage();
		}
	}
}

SERIA_Template::cssInclude(SERIA_Filesystem::getUrlFromPath(dirname(__FILE__).'/uploader.css'));

?>
<html>
	<head>
		<title>File upload frame</title>
	</head>
	<body>
		<script type='text/javascript'>
			<!--
				function anUploadStarts()
				{
					if (typeof(top.uploadStarts) != 'undefined')
						top.uploadStarts();
					else
						alert('No filearchive parent found.');
				}
				function changedFile(element)
				{
					if (element.value) {
						anUploadStarts();
						element.form.submit();
					}
				}
			-->
		</script>
		<form method='post' enctype='multipart/form-data'>
			<div id='form_area'>
				<label class='text' for='fileupload_browse'>Upload a file: </label><input type='file' id='fileupload_browse' name='uploader' value='' onchange='changedFile(this);' />
			</div>
		</form>
		<?php
			if ($uploaded !== false) {
				?>
					<script type='text/javascript'>
						<!--
							if (typeof(top.uploadedAFile) != 'undefined')
								top.uploadedAFile(<?php echo $uploaded; ?>);
							else
								alert('No filearchive parent found.');
						-->
					</script>
				<?php
			}
			if ($error !== false) {
				SERIA_ScriptLoader::loadScript('jQuery', '1.2.6', '1.2.3');
				?>
					<script type='text/javascript'>
						<!--
							$(document).ready(function () {
								if (typeof(top.uploadFailure) != 'undefined')
									top.uploadFailure(<?php echo SERIA_Lib::toJSON($error); ?>);
								else
									alert(<?php echo SERIA_Lib::toJSON($error); ?>);
							});
						-->
					</script>
				<?php
			}
		?>
	</body>
</html>
