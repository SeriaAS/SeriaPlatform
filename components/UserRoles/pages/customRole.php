<?php

require_once(dirname(__FILE__).'/../../../main.php');

if (!isset($_GET['user']))
	throw new SERIA_Exception('Must provide user id!');
if (!isset($_GET['from']))
	throw new SERIA_Exception('Must provide from url!');

$roles = new UserRoles(SERIA_User::createObject($_GET['user']));
$action = $roles->getCustomRoleAction();

if ($action->success) {
	header('Location: '.$_GET['from']);
	die();
}

$gui = new SERIA_GUI($title = _t('Add custom role'));
$gui->activeMenuItem('controlpanel/users');

ob_start();

?>

<h1 class='legend'><?php echo htmlspecialchars($title); ?></h1>

<?php echo $action->begin(); ?>

<div>
	<p><?php echo htmlspecialchars(_t('Please provide both the role-name (technical name) and caption (display name) below:')); ?></p>

	<table cellspacing='0'>
		<tr>
			<th><?php echo $action->label('role'); ?></th>
			<td><?php echo $action->field('role'); ?></td>
			<?php
				if ($action->errors) {
					?>
						<td>
							<?php
								if (isset($action->errors['role'])) {
									?>
										<p class='error'><?php echo htmlspecialchars($errors['role']); ?></p>
									<?php
								}
							?>
						</td>
					<?php
				}
			?>
		</tr>
		<tr>
			<th><?php echo $action->label('caption'); ?></th>
			<td><?php echo $action->field('caption'); ?></td>
			<?php
				if ($action->errors) {
					?>
						<td>
							<?php
								if (isset($action->errors['caption'])) {
									?>
										<p class='error'><?php echo htmlspecialchars($errors['caption']); ?></p>
									<?php
								}
							?>
						</td>
					<?php
				}
			?>
		</tr>
	</table>

	<div>
		<?php echo $action->submit(); ?>
		<button type='button' onclick="<?php echo htmlspecialchars('location.href = '.SERIA_Lib::toJSON($_GET['from']).';'); ?>"><?php echo htmlspecialchars(_t('Cancel')); ?></button>
	</div>
</div>
<?php
echo $action->end();

$gui->contents(ob_get_clean());

echo $gui->output();
