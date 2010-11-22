<?php

$title = _t('Redirecting..');
$gui = new SERIA_Gui($title);

$gui->title($title);

ob_start();

?>
	<h1 class='legend'>{{$title}}</h1>
	<p><?php echo _t('You are being redirected to the following page.'); ?></p>
	<div><a href="{{url|htmlspecialchars}}">{{location|htmlspecialchars}}</a></div>
	<h2><?php echo _t('Backtrace'); ?></h2>
	<div>
		<?php
			foreach ($this->backtrace as $ln)
				echo '<div>'.$ln.'</div>';
		?>
	</div>
	<h2><?php echo _t('Session data'); ?></h2>
	<div>
		<pre>
			<?php print_r($_SESSION); ?>
		</pre>
	</div>
	<h2><?php echo _t('Post data'); ?></h2>
	<div>
		<pre>
			<?php print_r($_POST); ?>
		</pre>
	</div>
	<h2><?php echo _t('Get data'); ?></h2>
	<div>
		<pre>
			<?php print_r($_GET); ?>
		</pre>
	</div>
	<h2><?php echo _t('Cookies'); ?></h2>
	<div>
		<pre>
			<?php print_r($_COOKIE); ?>
		</pre>
	</div>
<?php

$gui->contents(ob_get_clean());

echo $gui->output();