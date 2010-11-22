<?php
	require('common.php');
	$id = $_GET['id'];
	
	SERIA_Base::pageRequires('admin');
	
	$menuItem = SERIA_SiteMenus::find($id);
	if (!$menuItem) {
		SERIA_HtmlFlash::error(_t('The requested menu item was not found'));
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
	}
	
	$optionsMenu->addLink('<< Cancel', SERIA_HTTP_ROOT . '/seria/sitemenu/');
	
	if ($_POST['delete_item']) {
		try {
			$menuItem->deleteWithChildren();
			SERIA_HtmlFlash::notice(_t('The requested menu structure was successfully deleted'));
		} catch (Exception $exception) {
			SERIA_HtmlFlash::error(_t('The requested menu structure could not be deleted: %ERROR%', array('ERROR' => $exception->getMessage())));
		}
		
		SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
	}
?>
<h1 class="legend"><?php echo _t('Delete menu item %TITLE%', array('TITLE' => htmlspecialchars($menuItem->title))); ?></h1>

<p>
	<?php echo _t('Are you sure you want to delete this menu item (%TITLE%)?', array('TITLE' => htmlspecialchars($menuItem->title))); ?> 
</p>

<?php if (sizeof($menuItem->getChildren())) { ?>
	<p>
		<?php echo _t('<strong>This menu item has children. All children of this menu item will also be deleted.</strong>'); ?>
	</p>
<?php } ?>

<form action="" method="post">
	<input name="delete_item" type="submit" value="<?php echo 'Delete item'; ?>" />
</form>

<?php require_once('common_tail.php'); ?>

