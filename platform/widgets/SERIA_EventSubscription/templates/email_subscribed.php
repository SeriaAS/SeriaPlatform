<html>
	<head>
		<title>{{'You have beed subscribed to: %0%'|_t(title)}}</title>
	</head>
	<body>
		<?php
			ob_start();
			?><a href="{{link|htmlspecialchars}}">{{link}}</a><?php
			$link = ob_get_clean();
		?>
		<p>{{'You have been successfully registered as participant to: %0%. For more information, please open: %1%'|_t(title, $link)}}.</p>
	</body>
</html>