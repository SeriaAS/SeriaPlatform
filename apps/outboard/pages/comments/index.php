<?php

/*
 * Meta template..
 */
if (!isset($this)) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	$tpl = new SERIA_MetaTemplate();
	if (isset($_GET['node']) && $_GET['node'])
		$tpl->addVariable('nodeId', $_GET['node']);
	else
		$tpl->addVariable('nodeId', false);
	die($tpl->parse(__FILE__));
} else {

	SERIA_Base::pageRequires('login');

	?><s:gui title="{'Moderate comments'|_t|htmlspecialchars}">
		<h1 class='legend'>{{'Moderate comments'|_t}}</h1>
		<?php
			$query = SERIA_Meta::all('SERIA_Comment');
			if ($this->nodeId !== false)
				$query->where('metaObject = :node', array('node' => $this->nodeId));
			$grid = new SERIA_MetaGrid($query);
			function commentRowDisplayInTable($row)
			{
				$editUrl = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/apps/outboard/pages/comments/edit.php');
				$editUrl->setParam('id', $row->get('id'));
				$editUrl->setParam('return', SERIA_Url::current()->__toString());
				ob_start();
				?>
					<tr onclick="location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($editUrl->__toString())); ?>;">
						<td><?php echo $row->get('subset'); ?></td>
						<td><?php echo htmlspecialchars(strip_tags($row->get('title'))); ?></td>
						<td><?php echo htmlspecialchars(strip_tags($row->get('displayName'))); ?></td>
						<td><?php echo _t($row->get('approved') ? 'Approved' : ($row->get('rejected') ? 'Rejected' : 'Awaiting approval')); ?></td>
						<td>
							<?php
							$approveAction = $row->approveAction();
							if ($approveAction->invoked()) {
								if (!$approveAction->success)
									SERIA_HtmlFlash::error(_t('Failed to approve comment!'));
								SERIA_Base::redirectTo($approveAction->removeFromUrl(SERIA_Url::current())->__toString());
								die();
							}
							$rejectAction = $row->rejectAction();
							if ($rejectAction->invoked()) {
								if (!$rejectAction->success)
									SERIA_HtmlFlash::error(_t('Failed to reject comment!'));
								SERIA_Base::redirectTo($rejectAction->removeFromUrl(SERIA_Url::current())->__toString());
								die();
							}
							$deleteAction = $row->deleteAction();
							if ($deleteAction->invoked()) {
								if (!$deleteAction->success)
									SERIA_HtmlFlash::error(_t('Failed to delete comment!'));
								SERIA_Base::redirectTo($deleteAction->removeFromUrl(SERIA_Url::current())->__toString());
								die();
							}
							if (!$row->get('approved')) {
								?><a title="{{'Approve'|_t|htmlspecialchars}}" href="<?php echo htmlspecialchars($approveAction->__toString()); ?>"><span class='ui-icon ui-icon-check' style='float: left;'>{{'Approve'|_t}}</span></a><?php
							}
							if (!$row->get('rejected')) {
								?><a title="{{'Reject'|_t|htmlspecialchars}}" href="<?php echo htmlspecialchars($rejectAction->__toString()); ?>"><span class='ui-icon ui-icon-cancel' style='float: left;'>{{'Reject'|_t}}</span></a><?php
							}
							?>
							<a title="{{'Edit'|_t|htmlspecialchars}}" href="<?php echo htmlspecialchars($editUrl->__toString()); ?>"><span class='ui-icon ui-icon-pencil' style='float: left;'>{{'Edit'|_t}}</span></a>
							<a onclick='return false;' name="<?php echo htmlspecialchars($row->get('title')); ?>" class='deleteAction' title="{{'Delete'|_t|htmlspecialchars}}" href="<?php echo htmlspecialchars($deleteAction->__toString()); ?>"><span class='ui-icon ui-icon-trash' style='float: left;'>{{'Delete'|_t}}</span></a>
						</td>
					</tr>
				<?php
				return ob_get_clean();
			}
			echo $grid->output(array('subset', 'title', 'displayName', _t('Approved')), 'commentRowDisplayInTable');
		?>
		<script type='text/javascript'>
			<!--
				(function () {
					var deleteAction = function (obj) {
						var href = obj.getAttribute('href');
						var title = obj.getAttribute('name');
						var confirmText = <?php echo SERIA_Lib::toJSON(_t('Are you sure you want to delete %TITLE%?', array('TITLE' => '%TITLE%'))); ?>;
						confirmText = confirmText.replace('%TITLE%', title);
						if (confirm(confirmText))
							location.href = href;
						return false;
					};
					$('.deleteAction').each(function () {
						var obj = this;
						var handler = function (e) {
							if (!e)
								var e = window.event;
							e.cancelBubble = true;
							if (e.stopPropagation)
								e.stopPropagation();
							return deleteAction(obj);
						};

						if (this.addEventListener)
							this.addEventListener('click', handler, false);
						else
							this.attachEvent('onclick', handler);
					});
				})();
			-->
		</script>
	</s:gui>
	<?php
}