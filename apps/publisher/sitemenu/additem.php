<?php
	require('common.php');
	
	$optionsMenu->addLink(_t('<< Cancel'), SERIA_HTTP_ROOT . '/seria/sitemenu/');
	
	if ($_GET['edit']) {
		$id = $_GET['edit'];
		$menu = SERIA_SiteMenus::find($id);
	}
	if (!$menu) {
		$menu = new SERIA_SiteMenu();
		$menu->parentIdFromForm = (int) $_GET['parent_id'];
		$menu->name = '';
	} else {
		if ($article_id = $_GET['attachto']) {
			$article = SERIA_Article::createObjectFromId((int) $article_id);
			if ($article) {
				$articleRelation = $menu->Article;
				if (!$articleRelation->id) {
					$articleRelation = new SERIA_SiteMenuArticle();
					$articleRelation->sitemenu_id = $menu->id;
				}
				$articleRelation->article_id = $article->get('id');
				$articleRelation->url = $article->getUrl(array('menudefault', 'default'));
				$articleRelation->inheritpublishstatus = false;
				$articleRelation->save();
				
				$menu->relationtype = 'article';
				$menu->save();
				
				SERIA_HtmlFlash::notice(_t('The menu item was successfully attached to the requested article.'));
				SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php?edit=' . $menu->id . '&attach=article#attach');
				die();
			}
		}
	}
	
	$menu->addCustomRule('parentIdFromForm', 'required', _t('A parent element is required'));
	
	if ($menu->fromPost()) {
		if ($menu->isValid()) {
			$menu->save();
			SERIA_HtmlFlash::notice(_t('The menu element was successfully saved.'));
			SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
		}
	}

	$form = new SERIA_HtmlForm($menu);
	
	$parentElementTree = SERIA_SiteMenu::getSelectBoxTree($menu);
	$parentElementTree[0] = _t('Select parent');
	
	$dummyChecked = '';
	$urlChecked = '';
	$articleChecked = '';
	if ($menu->id) {
		switch ($menu->relationtype) {
			case 'dummy':
			case '':
				$dummyChecked = 'checked="checked"';
				break;
			case 'url':
				$urlChecked = 'checked="checked"';
				break;
			case 'article':
				$articleChecked = 'checked="checked"';
				break;
		}
	}
	
	$flashHtml = SERIA_HtmlFlash::getHtml();
?>

<h1 class="legend"><?php echo _t('Add menu item'); ?></h1>

<div class="tabs">
	<ul class="tabs">
		<li><a href="#edit"><span><?php echo _t('Edit'); ?></span></a></li>
		<li><a href="#attach"><span><?php echo _t('Attach'); ?></span></a></li>
	</ul>
</div>

<div id="edit">
	<?php echo $flashHtml; ?>

	<?php echo $form->start(); ?>
	
		<?php echo $form->errors() ?>
	
		<p>
			<?php echo $form->label('title', _t('Title: ')); ?><br />
			<?php echo $form->text('title'); ?>
		</p>
		<p>
			<?php echo $form->label('parentIdFromForm', _t('Parent menu element: ')); ?><br />
			<?php echo $form->select('parentIdFromForm', $parentElementTree); ?>
		</p>
		<p>
			<?php echo $form->checkbox('ispublished'); ?>
			<?php echo $form->label('ispublished', _t('Publish menu item')); ?>
		</p>
		<p>
			<?php echo $form->submit(_t('Save menu item')); ?>
		</p>
	<?php echo $form->end(); ?>
</div>
<div id="attach">
	<?php echo $flashHtml; ?>
	<?php if (!$menu->id) { ?>
		<p>
			<?php echo _t('The menu item must be saved before it can be attached'); ?> 
		</p>
	<?php } else { ?>
		<script type="text/javascript">
			function updateAttachView(enableRedirect) {
				$('#urlSelect').hide();
				$('#articleSelect').hide();
				$('#nothingSelect').hide();

				if ($('#dummy').attr('checked')) {
					$('#nothingSelect').show();
				}
				
				if ($('#url').attr('checked')) {
					$('#urlSelect').show();
				}
				
				if ($('#article').attr('checked')) {
					if (enableRedirect) {
						<?php if ($_GET['attach'] !== 'article') { ?>
							location.href = SERIA_VARS.HTTP_ROOT + '/seria/sitemenu/additem.php?edit=<?php echo $menu->id; ?>&attach=article#attach';
						<?php } ?>
					}
					$('#articleSelect').show();
				}
			}
		
			$(function() {
				<?php if ($_GET['attach'] == 'article') { ?>
					$('#article').attr('checked', 'checked');
				<?php } ?>
				updateAttachView(false);
				
				$('#dummy').bind('click', function() {
					updateAttachView(true);
				});
				$('#url').bind('click', function() {
					updateAttachView(true);
				});
				$('#article').bind('click', function() {
					updateAttachView(true);
				});
			});
		</script>
	
		<p>
			<?php echo _t('Attach to:'); ?>
			<input type="radio" id="dummy" name="attach" value="dummy" <?php echo $dummyChecked; ?> /><label for="dummy"><?php echo _t('Nothing'); ?></label>
			<input type="radio" id="url" name="attach" value="url" <?php echo $urlChecked; ?> /><label for="url"><?php echo _t('URL'); ?></label>
			<input type="radio" id="article" name="attach" value="article" <?php echo $articleChecked; ?> /><label for="article"><?php echo _t('Article'); ?></label>
		</p>
		
		<div id="nothingSelect">
			<?php
				if (isset($_POST['attachToNothing'])) {
					$menu->relationtype = 'dummy';
					if ($menu->Article) {
						$menu->Article->delete();
					}
					if ($menu->Url) {
						$menu->Url->delete();
					}
					$menu->save();
					SERIA_HtmlFlash::notice(_t('Menu item was successfully saved'));
					SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
				}
			?>
			<?php $form = new SERIA_HtmlForm('attachToNothing'); ?>
			<?php echo $form->start(); ?>
				<?php echo $form->hidden('nothing', 'nothing'); ?>
				<?php echo $form->submit(_t('Save')); ?>
			<?php echo $form->end(); ?>
		</div>
		
		<div id="urlSelect">
			<?php
				$urlObject = $menu->Url;
				if (!$urlObject) {
					$urlObject = new SERIA_SiteMenuUrl();
					$urlObject->sitemenu_id = $menu->id;
				}
				
				if ($urlObject->fromPost() && $urlObject->isvalid()) {
					$urlObject->save();
					$menu->relationtype = 'url';
					$menu->save();
					SERIA_HtmlFlash::notice(_t('Link URL was successfully saved'));
					SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/');
				}
				
				$urlForm = new SERIA_HtmlForm($urlObject);
			?>
			
			<?php echo $urlForm->start(); ?>
				<p>
					<?php echo $urlForm->label('url', _t('URL to link to: ')); ?>
					<?php echo $urlForm->text('url'); ?>
				</p>
				
				<p>
					<?php echo $urlForm->submit(_t('Save')); ?>
				</p>
			<?php echo $urlForm->end(); ?>
		</div>
		<div id="articleSelect">
			<?php if (($menu->relationtype == 'article') &&  $menu->Article->id) { ?>
				<?php
					$article = SERIA_Article::createObjectFromId((int) $menu->Article->article_id);
				?>
				<p>
					<?php
						echo _t('<strong>Warning: </strong>If a menu item is attach to an article the menu item will automatically be deleted, including all its children, if the article is deleted.');
					?>
				</p>
				<p>
					<?php echo _t('This menu item is currently attached to article: '); ?>
					<?php echo htmlspecialchars($article->getTitle()); ?>
				</p>
				
				<?php if ($menu->Article->id) { ?>
					<h2><?php echo _t('Publishing'); ?></h2>
					<?php
						$articleRelation = $menu->Article;
						if (!$articleRelation) {
							$articleRelation = new SERIA_SiteMenuArticle();
							$articleRelation->sitemenu_id = $menu->id;
							$articleRelation->inheritpublishstatus = 1;
						}
						if ($articleRelation->fromPost() && $articleRelation->isValid()) {
							if ($articleRelation->inheritpublishstatus) {
								$article = SERIA_Article::createObjectFromId($articleRelation->article_id);
								$menu->ispublished = $article->getPublishStatus();
								$menu->save();
							}
							$articleRelation->save();
							SERIA_HtmlFlash::notice(_t('Publish setting was successfully updated'));
							SERIA_Base::redirectTo(SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php?edit=' . $menu->id . '&attach=article&' . time() . '#attach');
							die();
						}
						$form = new SERIA_HtmlForm($articleRelation);
					?>
					<?php echo $form->start(); ?>
					<p>
						<?php echo $form->checkbox('inheritpublishstatus'); ?>
						<?php echo $form->label('inheritpublishstatus', _t('Inherit publish status from article')); ?><br />
					</p>
					<p>
						<?php echo $form->submit('Save'); ?>
					</p>
					<?php echo $form->end(); ?>
				<?php } ?>
				
				<h2><?php echo _t('Attach to another article: '); ?></h2>
			<?php } ?>
		
			<?php
				$widget = SERIA_Widget::getWidget('SERIA_ArticleSearch', 'sitemenu_edit');
				$widget->setUrlTail('#attach');
				$widget->linkResultTo = SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php?edit=' . $menu->id . '&amp;attach=article&amp;attachto={ID}#attach';
				$widget->render();
			?>
			
		</div>
	<?php } ?>
</div>
<?php 
	require('common_tail.php');
?>
