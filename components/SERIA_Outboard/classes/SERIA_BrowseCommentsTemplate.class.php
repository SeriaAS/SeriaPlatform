<?php

/**
 *
 * Some template-code must be globally available.
 * @author Jan-Espen
 *
 */
class SERIA_BrowseCommentsTemplate
{
	/**
	 *
	 * A row-template for the browse-comments table. (Flat-table)
	 * @param $row
	 */
	public static function browseCommentFilter($row)
	{
		$editUrl = new SERIA_Url(SERIA_HTTP_ROOT.'/seria/apps/outboard/pages/comments/edit.php');
		$editUrl->setParam('id', $row->get('id'));
		$editUrl->setParam('return', SERIA_Url::current()->__toString());
		ob_start();
		?>
			<tr onclick="location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($editUrl->__toString())); ?>">
				<td><?php echo $row->get('user'); ?></td>
				<td><?php echo $row->get('createdDate'); ?></td>
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
								<a id='<?php echo $eid; ?>' target='_blank' href="<?php echo htmlspecialchars($obj['url']); ?>"><?php echo $obj['url']; ?></a>
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
						echo _t("Low quality approved");
					else if($row->get('hidden'))
						echo _t('Rejected');
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
						if (!$row->get('hidden')) {
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
}