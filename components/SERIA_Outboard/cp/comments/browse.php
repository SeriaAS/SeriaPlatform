<?php
	require('../common.php');
	$gui->title(_t("Browse comments"));
	$gui->activeMenuItem('controlpanel/outboard/comments/browse');

	$contents = '<h1 class="legend">'._t("Browse all comments").'</h1>';
	function browseCommentFilter($row)
	{
		$editUrl = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/apps/outboard/pages/comments/edit.php');
		$editUrl->setParam('id', $row->get('id'));
		$editUrl->setParam('return', SERIA_Url::current()->__toString());
		ob_start();
		?>
			<tr onclick="location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($editUrl->__toString())); ?>">
				<td><?php echo $row->get('user'); ?></td>
				<td>
					<?php
						$obj = $row->get('metaObject');
						if ($obj instanceof SERIA_IWebLocation) {
							$id = strip_tags($obj->getTitle());
							if (strlen($id) > 15)
								$id = substr($id, 0, 12).'...';
							if (!$id) {
								if (isset($obj['id']))
									$id = $obj['id'];
								else
									$id = '--';
							}
							$resUrl = $obj->getUrl();
						} else {
							if (isset($obj['id']))
								$id = $obj['id'];
							else
								$id = '--';
							if (isset($obj['url']))
								$resUrl = $obj['url'];
							else
								$resUrl = false;
						}
						if ($resUrl) {
							$eid = 'eid'.mt_rand();
							?>
								<a id='<?php echo $eid; ?>' target='_blank' href="<?php echo htmlspecialchars($obj['url']); ?>"><?php echo $id; ?></a>
								<script type='text/javascript'>
									(function (element) {
										var cancelBubble = function (e) {
											if (!e)
												var e = window.event;
											e.cancelBubble = true;
											if (e.stopPropagation)
												e.stopPropagation();
										}
										if (element.addEventListener)
											element.addEventListener('click', cancelBubble, false);
										else
											element.attachEvent('onclick', cancelBubble);
									})(document.getElementById('<?php echo $eid; ?>'));
								</script>
							<?php
						} else {
							echo $id;
						}
					?>
				</td>
				<td><?php echo $row->get('title'); ?></td>
				<td><?php 
					if($row->get('flagged'))
						echo _t("Flagged");
					else if($row->get('approved'))
						echo _t("Approved");
					else if($row->get('rejected'))
						echo _t("Rejected");
					else
						echo _t("Awaiting approval");
				?></td>
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
							?><a title="<?php echo htmlspecialchars(_t('Approve')); ?>" href="<?php echo htmlspecialchars($approveAction->__toString()); ?>"><span class='ui-icon ui-icon-check' style='float: left;'><?php echo _t('Approve'); ?></span></a><?php
						}
						if (!$row->get('rejected')) {
							?><a title="<?php echo htmlspecialchars(_t('Reject')); ?>" href="<?php echo htmlspecialchars($rejectAction->__toString()); ?>"><span class='ui-icon ui-icon-cancel' style='float: left;'><?php echo _t('Reject'); ?></span></a><?php
						}
					?>
					<a title="<?php echo htmlspecialchars(_t('Edit')); ?>" href="<?php echo htmlspecialchars($editUrl->__toString()); ?>"><span class='ui-icon ui-icon-pencil' style='float: left;'><?php echo _t('Edit'); ?></span></a>
					<a onclick='return false;' name="<?php echo htmlspecialchars($row->get('title')); ?>" class='deleteAction' title="<?php echo htmlspecialchars(_t('Delete')); ?>" href="<?php echo htmlspecialchars($deleteAction->__toString()); ?>"><span class='ui-icon ui-icon-trash' style='float: left;'><?php echo _t('Delete'); ?></span></a>
				</td>
			</tr>
		<?php
		return ob_get_clean();
	}

	ob_start();
	?>
		<form method='get'>
			<div>
				<table>
					<thead>
					</thead>
					<tfoot>
						<td colspan='2'>
							<input id='refreshGrid' type='submit' value="<?php echo htmlspecialchars(_t('Show')); ?>" />
						</td>
					</tfoot>
					<tbody>
						<th><?php echo htmlspecialchars(_t('Approval')); ?></th>
						<td>
							<?php
								if (isset($_GET['approval']))
									$approval = $_GET['approval'];
								else
									$approval = false;
								$approval_ch = array('all', 'unmoderated', 'approved', 'rejected', 'flagged');
								if (!in_array($approval, $approval_ch))
									$approval = $approval_ch[0];
							?>
							<select id='approval' name='approval'>
								<option value='all'<?php if ($approval == 'all') echo ' selected=\'selected\''; ?>><?php echo _t('All'); ?></option>
								<option value='unmoderated'<?php if ($approval == 'unmoderated') echo ' selected=\'selected\''; ?>><?php echo _t('Unmoderated'); ?></option>
								<option value='approved'<?php if ($approval == 'approved') echo ' selected=\'selected\''; ?>><?php echo _t('Approved'); ?></option>
								<option value='rejected'<?php if ($approval == 'rejected') echo ' selected=\'selected\''; ?>><?php echo _t('Rejected'); ?></option>
								<option value='flagged'<?php if ($approval == 'flagged') echo ' selected=\'selected\''; ?>><?php echo _t('Flagged'); ?></option>
							</select>
						</td>
					</tbody>
				</table>
			</div>
		</form>
		<script type='text/javascript'>
			<!--
				(function (approval) {
					var change = function (e) {
						approval.form.submit();
					}
					if (approval.addEventListener)
						approval.addEventListener('change', change, false);
					else
						approval.attachEvent('onchange', change); 
				})(document.getElementById('approval'));
				document.getElementById('refreshGrid').style.display = 'none';
			-->
		</script>
	<?php
	$contents .= ob_get_clean();

	$comments = SERIA_Meta::all('SERIA_Comment');
	if ($approval != 'all') {
		if ($approval == 'unmoderated')
			$comments->where('(rejected = 0 OR rejected IS NULL) AND (approved = 0 OR approved IS NULL)');
		else if ($approval == 'approved')
			$comments->where('approved != 0');
		else if ($approval == 'rejected')
			$comments->where('rejected != 0');
		else if ($approval == 'flagged')
			$comments->where('flagged = 1');
	}
	$comments->order('-createdDate');
	$grid = new SERIA_MetaGrid($comments);
	$contents .= $grid->output(array('user' => 150, 'metaObject', 'title', 'approved' => 125, _t('Operations') => 100), 'browseCommentFilter');

	ob_start();
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
	<?php
	$contents .= ob_get_clean();

	$gui->contents($contents);

	echo $gui->output();
