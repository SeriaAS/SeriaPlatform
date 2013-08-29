<?php
	SERIA_Template::disable();
	header('Content-Type: text/javascript');

	if (!isset($_GET['probe']))
		return;
	$probe = $_GET['probe'];
	if (!is_array($probe))
		$probe = array($probe);
?>

(function () {
	var pollCallbacks = [];
	var pollRunning = false;
	var pollEnded = false;
	var pollIntervalObj;
	var pollCount = 0;
	var pollUrl = <?php echo SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'/seria/api/?apiPath=SAPI_ExternalReq2/checkLogin'); ?>;

	metaSsoProbeLoginPoller_run = function () {
		if (pollEnded) {
			window.clearInterval(pollIntervalObj);
			return;
		}
		pollCount++;
		if (pollCount > 200)
			pollEnded = true;
		jQuery.ajax({
			'url': pollUrl,
			'type': 'GET',
			'success': function (data, textStatus, jqXHR) {
				if (data && data.loggedIn) {
					pollEnded = true;
					for (var i = 0; i < pollCallbacks.length; i++)
						pollCallbacks[i](true);
				}
			}
		});
	};
	var metaSsoProbeLoginPollerActivate = function () {
		pollRunning = true;
		pollIntervalObj = window.setInterval(function () {
			metaSsoProbeLoginPoller_run();
		}, 500);
	};
	metaSsoProbeLoginPoller = function (callback) {
		pollCallbacks[pollCallbacks.length] = callback;
		if (!pollRunning)
			metaSsoProbeLoginPollerActivate();
	};

	var addIframe = function (container, url) {
		var iframe = document.createElement('iframe');
		iframe.src = url;
		container.appendChild(iframe);
	};

	var div = document.createElement('div');
	div.style.display = 'none';
	document.body.appendChild(div);
	<?php
		foreach ($probe as $classid) {
			$delim = strpos($classid, ':');
			$class = substr($classid, 0, $delim);
			$id = substr($classid, $delim + 1);
			$probeUrl = SERIA_Meta::manifestUrl('Authproviders', 'metaSsoProbe', array(
				'class' => $class,
				'id' => $id
			));
			?>
			addIframe(div, <?php echo SERIA_Lib::toJSON($probeUrl->__toString()); ?>);
			<?php
		}
	?>
})();