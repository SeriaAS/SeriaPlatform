<?php

require_once(dirname(__FILE__).'/../../../../main.php');

SERIA_ScriptLoader::loadScript('jQuery-ui', '1.5.3'); /* Requires jQuery 1.2.6 */
SERIA_ScriptLoader::loadScript('jQuery', '1.3.1', '1.3.1'); /* This should upgrade jQuery-ui */

?>
<html>
	<head>
		<title><?php echo _t('Test'); ?></title>
	</head>
	<body>
	</body>
</html>