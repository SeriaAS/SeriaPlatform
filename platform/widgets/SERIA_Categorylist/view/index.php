<ul class="CategoryListWidget">
	<?php foreach ($this->categories as $category) { ?>
		<li <?php echo $category->getContextMenu(); ?>><?php echo $category->get("name"); ?></li>
	<?php } ?>
</ul>