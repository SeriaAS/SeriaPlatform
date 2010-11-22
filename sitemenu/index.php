<?php require('common.php'); ?>
<h1 class="legend"><?php echo _t('Edit menu'); ?></h1>
<?php
	$optionsMenu->addLink(_t('Create menu item'), SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php');
	
	function sitemenu_publishMenuToList($menu) {
		static $level = 0;
		if ($menu->title) {
			$title = $menu->title;
		} else {
			$title = $menu->name;
		}
		$title = htmlspecialchars($title);
		
		$separator = str_repeat('&nbsp; ', $level);
		
		static $itemNo = 0;
		
		if ($parent = $menu->getParent()) {
			$parent_id = $parent->id;
		} else {
		}
		
		$statusMenu = '';
		if ($menu->ispublished) {
			$status = _t('Published');
			if (!$menu->isRoot()) {
				$url = SERIA_HTTP_ROOT . '/seria/sitemenu/setpublish.php?publish=0&amp;id=' . $menu->id;
				$statusMenu = 'mnu="' . _t('Unpublish') . ':top.location.href=\'' . $url . '\'"';
			}
		} else {
			$status = _t('Not published');
			if (!$menu->isRoot()) {
				$url = SERIA_HTTP_ROOT . '/seria/sitemenu/setpublish.php?publish=1&amp;id=' . $menu->id;
				$statusMenu = 'mnu="' . _t('Publish') . ':top.location.href=\'' . $url . '\'"';
			}
		}
		
		$class = '';
		if (!$menu->isRoot()) {
			$class = 'clickableCell';
			$onclick = 'onclick=\'top.location.href=' . json_encode(SERIA_HTTP_ROOT . '/seria/sitemenu/additem.php?edit=' . $menu->id) . '\''; 
		}
		
		echo '<tr class="draggableItem" id="menuItem_' . $menu->id . '">';
		echo '  <td ' . $menu->getContextMenu() . ' class="' . $class . '" ' . $onclick . '>';
		
		for ($i = 0; $i < ($level); $i++) {
			echo '<div class="menuIndentation"></div>';
		}
		
		echo '    <span>' . $title . '</span>';
		echo '  </td>';
		echo '  <td class="nobr" ' . $statusMenu . '>' . $status . '</td>';
		echo '</tr>';
		$level++;
		
		if ($menu->getChildren()) {
			foreach ($menu->getChildren() as $child) {
				sitemenu_publishMenuToList($child);
			}
		}
		
		$level--;
	}
	
	SERIA_ScriptLoader::loadScript('jQuery-ui');
	SERIA_ScriptLoader::loadScript('jQuery-ui-draggable');
	SERIA_ScriptLoader::loadScript('jQuery-ui-droppable');
?>

<script type="text/javascript">
/*	function menuSort_relocateItem(item, target) {
		$('#debug').prepend('Relocating ' + item.attr('id') + ' to before ' + target.attr('id') + '<br />');
		target.before(item);
	}

	$(function() {
		$('tr.draggableItem').draggable({
			helper: function () {
				return document.createElement('div');
			},
			axis: 'y',
			greedy: true,
			grid: [10, 10],
			drag: function (event, ui) {
				var dragY = ui.position.top;
				var targetItem;

				ui.helper.parent().children('tr.draggableItem').filter(':not(tr.ui-draggable-dragging)').each(function() {
					var item = $(this);
					var itemY = item.position().top;
					if (dragY > (itemY) && (dragY < (item.height() + itemY))) {
						targetItem = item;
					}
				});			
				if (targetItem.length) {
					menuSort_relocateItem($(this), targetItem);
				}

				return false;
			}
		});
	}); */
</script>

<?php SERIA_HtmlFlash::show(); ?>

<table class="sortableMenu grid">
	<thead>
		<tr>
			<th class="tableMaxWidth"><?php echo _t('Menu item'); ?></th>
			<th><?php echo _t('Status'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
			$menus = SERIA_SiteMenu::getRootElements();
		?>
		
		<?php foreach ($menus as $menu) { ?>
			<?php sitemenu_publishMenuToList($menu); ?>
		<?php } ?>
	</tbody>
</table>

<?php require('common_tail.php'); ?>