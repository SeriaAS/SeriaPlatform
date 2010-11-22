<?php $searchForm = new SERIA_HtmlForm('SERIA_ArticleSearchWidget'); ?>
<?php echo $searchForm->start(); ?>
	<p>
		<?php echo $searchForm->label('query', _t('Search: ')); ?>
		<?php echo $searchForm->text('query'); ?>
		<?php echo $searchForm->submit(_t('Search')); ?>
	</p>
<?php echo $searchForm->end(); ?>
