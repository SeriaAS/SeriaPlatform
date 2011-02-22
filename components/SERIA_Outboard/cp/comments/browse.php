<?php
	require('../common.php');
	$gui->title(_t("Browse comments"));
	$gui->activeMenuItem('controlpanel/outboard/comments/browse');

	$contents = '<h1 class="legend">'._t("Browse all comments").'</h1>';

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
								$approval_ch = array('all', 'unmoderated', 'approved', 'lowq', 'rejected', 'flagged');
								if (!in_array($approval, $approval_ch))
									$approval = $approval_ch[0];
							?>
							<select id='approval' name='approval'>
								<option value='all'<?php if ($approval == 'all') echo ' selected=\'selected\''; ?>><?php echo _t('All'); ?></option>
								<option value='unmoderated'<?php if ($approval == 'unmoderated') echo ' selected=\'selected\''; ?>><?php echo _t('Unmoderated'); ?></option>
								<option value='approved'<?php if ($approval == 'approved') echo ' selected=\'selected\''; ?>><?php echo _t('Approved'); ?></option>
								<?php
									/*
									 * Only show the low-quality category when it is in use.
									 */
									if ($approval == 'lowq' || SERIA_Meta::all('SERIA_Comment')->where('rejected != 0')->current()) {
										?>
											<option value='lowq'<?php if ($approval == 'lowq') echo ' selected=\'selected\''; ?>><?php echo _t('Low quality approved'); ?></option>
										<?php
									}
								?>
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
		else if ($approval == 'lowq')
			$comments->where('rejected != 0');
		else if ($approval == 'rejected')
			$comments->where('hidden != 0');
		else if ($approval == 'flagged')
			$comments->where('flagged = 1');
	}
	$comments->order('-createdDate');
	$grid = new SERIA_MetaGrid($comments);
	$contents .= $grid->output(array('user' => 150, 'createdDate', 'metaObject', 'title', 'approved' => 125, _t('Operations') => 100), array('SERIA_BrowseCommentsTemplate', 'browseCommentFilter'));

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
