<?php
	require('../common.php');
	$gui->activeMenuItem('controlpanel/settings/files/filetypes');
	ob_start();

	if(isset($_GET['id']))
		$fp = SERIA_Meta::load('SERIA_BlobType', $_GET['id']);
	else
		$fp = new SERIA_BlobType();

	$form = $fp->editAction();
	if($form->success)
	{
		header("Location: ".SERIA_HTTP_ROOT."/seria/components/SERIA_Blob/filetypes.php");
		die();
	}
?>
<h1 class="legend"><?php echo _t("Edit file type"); ?></h1>
	<?php echo $form->begin(); ?>
	<table><summary><?php echo _t("Editing file type"); ?></summary>
	<tbody>
		<tr>
			<th><?php echo $form->label("extension"); ?></th>
			<td><?php echo $form->field("extension"); ?></th>
		</tr>
		<tr>
			<th><?php echo $form->label("mediatype"); ?></th>
			<td><?php echo $form->field("mediatype"); ?></th>
		</tr>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="2"><?php echo $form->submit(_t("Save")); ?></td>
		</tr>
	</tfoot></table>


<?php 
	$contents = ob_get_contents(); 
	ob_end_clean(); 
	echo $gui->contents($contents)->output(); 
?>
