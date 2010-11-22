<?php
	$this->renderPartial('form');
?>
<hr />
<?php

	$articleQuery = $this->articleQuery;
	
	foreach ($articleQuery->page(0,100) as $article) {
		?>
			<p id="articleSearchResult_<?php echo $article->get('id'); ?>" class="<?php echo implode(' ', $this->resultCssClasses); ?>">
				<strong class="searchResultTitle"><?php
					if ($this->linkResultTo) { ?><a href="<?php echo str_replace('{ID}', $article->get('id'), $this->linkResultTo);
				?>"><?php } ?><?php echo htmlspecialchars($article->getTitle()); ?><?php if ($this->linkResultTo) { ?></a><?php } ?></strong>
				<em><?php echo $article->get('type'); ?>: </em> <?php echo htmlspecialchars(substr($article->getDescription(), 0, 100)); ?>
			</p>
		<?php
	}
?>