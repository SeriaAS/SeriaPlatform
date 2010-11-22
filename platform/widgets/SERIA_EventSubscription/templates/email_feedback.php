<html>
	<head>
		<title><?php echo htmlspecialchars($title); ?></title>
	</head>
	<body>
		<h1><?php echo htmlspecialchars($title); ?></h1>
		<p><?php echo htmlspecialchars(_t('After this event we would like to ask you to provide some feedback. Please take some time to submit this evaluation form:')); ?></p>
		<p><a href='<?php echo htmlspecialchars($url); ?>'><?php echo htmlspecialchars($url); ?></a></p>
	</body>
</html>