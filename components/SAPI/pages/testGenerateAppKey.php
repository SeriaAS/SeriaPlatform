<s:gui title="{'Test generate app-key'|_t}">
	<?php
		if (isset($_POST['appKey']))
			$appKey = $_POST['appKey'];
		else
			$appKey = NULL;
		$reqTarget = SERIA_Meta::manifestUrl('SAPI', 'generateAppKey', array('reqUrl' => SERIA_Url::current()->__toString()));
	?>
	<h1 class='legend'>{{'Test generate app-key'|_t}}</h1>
	<?php
	if ($appKey !== NULL) {
		?>
		<p>{{'Got app-key: %0%'|_t($appKey)}}</p>
		<?php
	} else {
		?>
		<p><a href="{{$reqTarget|htmlspecialchars}}">{{'Start test with default login'|_t}}</a></p>
		<?php
			$url = new SERIA_Url($reqTarget);
			$query = $url->getQuery(NULL);
			SERIA_Url::parse_str($query, $params);
			$url = $url->clearParams()->__toString();
		?>
		<p>{{'Or you can start it with a custom login page:'|_t}}</p>
		<form method='get' action="{{$url|htmlspecialchars}}">
			<?php
			foreach ($params as $name => $value) {
				?>
				<input type='hidden' name="{{$name|htmlspecialchars}}" value="{{$value|htmlspecialchars}}">
				<?php
			}
			?>
			<div>
				<label>{{'Custom login page (path): '|_t}}<input type='text' name='loginPath' value='/login.php'></label>
				<div><input type='submit' value="{{'Test'|_t|htmlspecialchars}}"></div>
			</div>
		</form>
		<?php
	}
	?>
</s:gui>