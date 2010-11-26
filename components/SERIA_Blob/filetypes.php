<?php
	require('common.php');
	$gui->activeMenuItem('controlpanel/settings/files/filetypes');
	ob_start();
?>
	<h1 class="legend"><?php echo _t("Filetypes"); ?></h1>
	<p><?php echo _t("Manage uploadable file types from here. If you want to enable your users to upload files, their file extension must be listed here along with a mime type that should be used to serve the file to browsers."); ?></p>
<?php
	$filetypes = SERIA_Meta::all('SERIA_BlobType');
	$grid = $filetypes->grid();
	echo $grid->output(array('extension','mediatype'));
	echo SERIA_GuiVisualize::toolbar(array(
		'<a href="filetypes/edit.php">'._t("Add filetype").'</a>',
		'<a href="index.php">'._t("Cancel")."</a>",
	));
?>
<?php
	$contents = ob_get_contents();
	ob_end_clean();
	echo $gui->contents($contents)->output();
