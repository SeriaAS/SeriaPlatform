<?php
	if (!SERIA_Base::user()) {
		SERIA_Base::pageRequires('login');
	}
?><s:gui title="{'Application keys for Seria Platform API subsystem'|_t}">
	<h1 class='legend'>{{'Application keys for Seria Platform API subsystem'|_t}}</h1>
	<?php
		$appkeysUrl = SERIA_Meta::manifestUrl('SAPI', 'appkeys');
		if ($appkeysUrl instanceof SERIA_Url)
			$appkeysUrl = $appkeysUrl->__toString();
		if (!isset($_GET['id']) || !$_GET['id'])
			SERIA_Base::redirectTo($appkeysUrl);
		$data = SAPI_SAPI::getAppKeyData($_GET['id']);
		if (!$data)
			SERIA_Base::redirectTo($appkeysUrl);
	?>
	<table class='grid'>
		<tfoot>
			<tr>
				<td colspan='2'>
					<?php
						$appKey = SERIA_Meta::load('SAPI_Token', $_GET['id']);
						$deleteAction = $appKey->deleteAction($appkeysUrl);
						$post = $deleteAction->getPostInvokeParams();
					?>
					<form method='post'>
						<?php
							foreach ($post as $name => $value) {
								?>
									<input type='hidden' name="{{$name|htmlspecialchars}}" value="{{$value|htmlspecialchars}}" />
								<?php
							}
						?>
						<div>
							<input type='submit' value="{{'Delete'|_t|htmlspecialchars}}" />
							<a href="{{$appkeysUrl|htmlspecialchars}}">{{'<< Back'|_t}}</a>
						</div>
					</form>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
				if ($data['user'] != SERIA_Base::user()->get('id')) {
					?>
						<tr>
							<th>{{'User: '|_t}}</th>
							<td>{{$data.user}} (<?php echo htmlspecialchars(SERIA_User::createObject($data['user'])->get('displayName')); ?>)</td>
						</tr>
					<?php
				}
			?>
			<tr>
				<th>{{'Description: '|_t}}</th>
				<td>{{$data.description}}</td>
			</tr>
			<tr>
				<th>{{'Secret: '|_t}}</th>
				<td><input type='text' value="{{$data.secret|htmlspecialchars}}" /></td>
			</tr>
		</tbody>
	</table>
</s:gui>