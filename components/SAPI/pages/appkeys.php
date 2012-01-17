<?php
	if (!SERIA_Base::user()) {
		SERIA_Base::pageRequires('login');
	}
?><s:gui title="{'Application keys for Seria Platform API subsystem'|_t}">
	<h1 class='legend'>{{'Application keys for Seria Platform API subsystem'|_t}}</h1>
	<?php echo SAPI::js(); ?>
	<table class='grid'>
		<thead>
			<tr>
				<th>{{'Key ID'}}</th>
				<th>{{'Description'}}</th>
			</tr>
		</thead>
		<tbody id='appkeys'>
			<?php
				$appkeys = SAPI_SAPI::getAppKeys();
				foreach ($appkeys as $id => $desc) {
					?>
						<tr>
							<td><a href="<?php echo htmlspecialchars(SERIA_Meta::manifestUrl('SAPI', 'appkey', array('id' => $id))); ?>">{{$id}}</a></td>
							<td>{{$desc}}</td>
						</tr>
					<?php
				}
			?>
		</tbody>
	</table>
	<?php
		$createAction = SAPI_Token::createAction();
		if ($createAction->success) {
			SERIA_Base::redirectTo(SERIA_Url::current()->__toString());
		}
		echo $createAction->begin();
	?>
	<h2>{{'New App-key'|_t}}</h2>
	<table>
		<thead>
		</thead>
		<tfoot>
			<tr>
				<td colspan='2'>
					<?php echo $createAction->submit(_t('Create App-key')); ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<tr>
				<th><?php echo $createAction->label('description', _t('Description: ')); ?></th>
				<td><?php echo $createAction->field('description'); ?></td>
			</tr>
		</tbody>
	</table>
	<?php
		echo $createAction->end();
	?>
	<?php
		if (SERIA_Base::isAdministrator()) {
			?>
				<h2>{{'Other users App-keys'|_t}}</h2>
			<?php
			$other = SERIA_Meta::all('SAPI_Token')->where('user != :user', array('user' => SERIA_Base::user()->get('id')));
			if ($other->count()) {
				?>
				<table class='grid'>
					<thead>
						<tr>
							<th>{{'User'|_t}}</th>
							<th>{{'Key ID'|_t}}</th>
							<th>{{'Description'|_t}}</th>
						</tr>
					</thead>
					<tbody id='appkeys'>
						<?php
							foreach ($other as $appkey) {
								$user = $appkey->get('user');
								?>
									<tr>
										<td><?php echo htmlspecialchars(($user ? $user->get('id') : 'NULL').' ('.($user ? $user->get('displayName') : 'NULL').')'); ?></td>
										<td><a href="<?php echo htmlspecialchars(SERIA_Meta::manifestUrl('SAPI', 'appkey', array('id' => $appkey->get('id')))); ?>"><?php echo $appkey->get('id'); ?></a></td>
										<td><?php echo $appkey->get('description'); ?></td>
									</tr>
								<?php
							}
						?>
					</tbody>
				</table>
				<?php
			} else {
				?><p>{{'No other App-keys'|_t}}</p><?php
			}
		}
	?>
</s:gui>