<ul class="NewsListWidget">
	<?php foreach ($this->articles as $article) { ?>
		<li <?php echo $article->getContextMenu(); ?>><a href="<?php echo $article->getNewsUrl(); ?>"><?php echo $article->get("title"); ?></a></li>
	<?php } ?>
</ul>