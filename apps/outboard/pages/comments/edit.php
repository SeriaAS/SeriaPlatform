<?php

/*
 * Meta template..
 */
if (!isset($this)) {
	require_once(dirname(__FILE__).'/../../../../main.php');
	$tpl = new SERIA_MetaTemplate();
	if (isset($_GET['id']) && $_GET['id']) {
		$tpl->addVariable('comment', SERIA_Meta::load('SERIA_Comment', $_GET['id']));
		$tpl->addVariable('title', 'Edit comment: %0%');
		if (isset($_GET['return']) && $_GET['return'])
			$tpl->addVariable('return', new SERIA_Url($_GET['return']));
		else
			$tpl->addVariable('return', SERIA_Url::current());
	} else {
		throw new SERIA_Exception('No comment!');
	}
	die($tpl->parse(__FILE__));
} else {

	SERIA_Base::pageRequires('login');

	SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/apps/outboard/pages/comments/edit.css');

	$commentTitle = $this->comment->get('title');
	?>
		<s:gui title="{title|_t($commentTitle)|htmlspecialchars}">
			<h1 class='legend'>{{title|_t($commentTitle)}}</h1>
			<?php
				$approveAction = $this->comment->approveAction();
				if ($approveAction->invoked()) {
					if (!$approveAction->success)
						SERIA_HtmlFlash::error(_t('Failed to approve comment!'));
					SERIA_Base::redirectTo($approveAction->removeFromUrl(SERIA_Url::current())->__toString());
					die();
				}
				$rejectAction = $this->comment->rejectAction();
				if ($rejectAction->invoked()) {
					if (!$rejectAction->success)
						SERIA_HtmlFlash::error(_t('Failed to reject comment!'));
					SERIA_Base::redirectTo($rejectAction->removeFromUrl(SERIA_Url::current())->__toString());
					die();
				}
				$deleteAction = $this->comment->deleteAction();
				if ($deleteAction->invoked()) {
					if (!$deleteAction->success)
						SERIA_HtmlFlash::error(_t('Failed to delete comment!'));
					SERIA_Base::redirectTo($this->return->__toString());
					die();
				}
				$unflagAction = $this->comment->unflagAction();
				if($unflagAction->success)
					SERIA_HtmlFlash::notice(_t("Comment was unflagged"));
				$action = $this->comment->editAction();
				if ($action->success) {
					SERIA_Base::redirectTo($this->return->__toString());
					die();
				}
				echo $action->begin();
			?>
			<table class='editAction'>
				<thead>
				</thead>
				<tfoot>
					<tr>
						<td colspan='2'>
							<input type='submit' value="{{'Save'|_t|htmlspecialchars}}" />
							<?php
							if (!$this->comment->get('approved')) {
								?><input type='button' onclick="location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($approveAction->__toString())); ?>" value="{{'Approve'|_t|htmlspecialchars}}" /><?php
							}
							if (!$this->comment->get('rejected')) {
								?><input type='button' onclick="location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($rejectAction->__toString())); ?>" value="{{'Reject'|_t|htmlspecialchars}}" /><?php
							}
							?>
							<script type='text/javascript'>
								<!--
									function deleteAction()
									{
										if (confirm(<?php echo SERIA_Lib::toJSON(_t('Are you sure you want to delete: %TITLE%?', array('TITLE' => $this->comment->get('title')))); ?>)) {
											location.href = <?php echo SERIA_Lib::toJSON($deleteAction->__toString()); ?>;
										}
									}
								-->
							</script>
							<input type='button' onclick="deleteAction();" value="{{'Delete'|_t|htmlspecialchars}}" />
							<input type='button' onclick="location.href = <?php echo htmlspecialchars(SERIA_Lib::toJSON($this->return->__toString())); ?>" value="{{'Cancel'|_t|htmlspecialchars}}" />
						</td>
					</tr>
				</tfoot>
				<tbody>
					<tr>
						<th><?php echo $action->label('title'); ?></th>
						<td><?php echo $action->field('title'); ?></td>
					</tr>
					<tr>
						<th><?php echo $action->label('message'); ?></th>
						<td><?php echo $action->field('message'); ?></td>
					</tr>
					<tr>
						<th><?php echo $action->label('displayName'); ?></th>
						<td><?php echo $action->field('displayName'); ?></td>
					</tr>
					<tr>
						<th>{{'Approval'|_t}}</th>
						<td class="text"><?php
							if($this->comment->get('rejected'))
								echo _t("Rejected");
							else if($this->comment->get('flagged'))
							{
								$fl = $this->comment->getLastFlagLog();
$reasons = array();
if($fl->get('flagSpam')) $reasons[] = _t("Spam");
if($fl->get('flagPersonalAttack')) $reasons[] = _t("Personal attack");
if($fl->get('flagRacist')) $reasons[] = _t("Racist");
if($fl->get('flagPorn')) $reasons[] = _t("Pornographic");
if($fl->get('flagCopyright')) $reasons[] = _t("Copyrighted");
if($fl->get('flagOther')) $reasons[] = _t("Other");

								echo _t("Flagged, because of: %REASONS%", array('REASONS' => implode(', ', $reasons)))." <br><a href='".$unflagAction."'>"._t("Unflag this comment")."</a>";
							}
							else if($this->comment->get('approved'))
								echo _t("Approved");
							else
								echo _t("Awaiting approval");
						?></td>
					</tr>
				</tbody>
			</table>
			<?php
				echo $action->end();
			?>
		</s:gui>
	<?php
}
