<?php
	require('common.php');

	$gui->activeMenuItem('controlpanel/outboard/comments');

	ob_start();
?>
	<?php
		$topCategories = $gui->getMenuItemsLevel(3);
	?>
	<h2><?php echo _t('What do you want to do?'); ?></h2>
	<ul>
	<?php
		$items = array();
		foreach ($topCategories as $id => $info) {
			?>
				<li><a href="<?php echo htmlspecialchars($info['url']); ?>"><?php echo $info['title']; ?></a></li>
			<?php
		}
	?>
	</ul>
<?php
	$contents .= ob_get_clean();
	
	$gui->contents($contents);

	echo $gui->output();
