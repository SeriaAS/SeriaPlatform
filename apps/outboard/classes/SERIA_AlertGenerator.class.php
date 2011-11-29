<?php

class SERIA_AlertGenerator
{
	protected $channel;

	public function __construct($channel)
	{
		$this->channel = $channel;
	}
	public function getPath()
	{
		$url = $this->getFilename();
		$parsed = parse_url($url);
		return $parsed['path'];
	}
	public function getFilename()
	{
		return SERIA_DYN_HTTP_ROOT.'/outboard/alerter/channel'.$this->channel->get('id').'.js';
	}
	public function output()
	{
		ob_start();
		?>
			if (typeof registerAlertMessageCallback == 'undefined') {
				(function () {
					var callbacks = new Array();
					registerAlertMessageCallback = function (callback) {
						callbacks[callbacks.length] = callback;
					}
					fireAlertMessageCallbacks = function () {
						for (var i = 0; i < callbacks.length; i++) {
							callbacks[i]();
						}
					}
				})();
			}
		<?php
			$callbacks_code = ob_get_clean(); /* grab this javascript code */
			ob_start();
			echo $callbacks_code; /* Put back into the output buffer */
		?>
			/*
			 * Global javascript
			 */
			var <?php echo ($myid = 'myid'.mt_rand()); ?> = {};

			(function () {
				/*
				 * Utility functions:
				 */
				var getMyScriptElement = function () {
					var refUrl = <?php echo SERIA_Lib::toJSON($this->getFilename()); ?>;
					var refPath = <?php echo SERIA_Lib::toJSON($this->getPath()); ?>;

					var scripts = document.getElementsByTagName('script');
					for (var i = 0; i < scripts.length; i++) {
						if (scripts[i].src.length < refUrl.length)
							continue;
						var part = scripts[i].src.substr(0, refUrl.length);
						if (part == refUrl) {
							return scripts[i];
						}
					}
					/*
					 * fallback
					 */
					for (var i = 0; i < scripts.length; i++) {
						if (scripts[i].src.indexOf(refUrl) >= 0) {
							return scripts[i];
						}
					}
					return false;
				}
				var $_GET = function (name) {
					var scriptElement = getMyScriptElement();
					var url = scriptElement.src;

					var exp = '(?:\\?([^#]*))?(?=#|$)';
					exp = new RegExp(exp);
					var query = url.match(exp);
					if (typeof(query[1]) == 'undefined')
						return false;
					query = query[1];

					/*
					 * Thanks to Josh Fraser on June 10, 2009.
					 * This regexp and code is based on the example published by Josh.
					 */
					exp = '&'+name+'(?:=([^&]*))?(?=&|$)';
					exp = new RegExp(exp,'i');
					var m = '&' + query;
					m = m.match(exp);
					if (typeof(m[1]) != 'undefined') {
						return decodeURIComponent(m[1]);
					} else {
						return false;
					}
				}
				var externalCallbackName = $_GET('callback');
				eval(externalCallbackName+'();');
				var addEvent = function (obj, evType, fn){ 
					if (obj.addEventListener){ 
						obj.addEventListener(evType, fn, false); 
						return true; 
					} else if (obj.attachEvent){ 
						var r = obj.attachEvent("on"+evType, fn); 
						return r; 
					} else { 
						return false; 
					} 
				}
				var setCookie = function(name, value, expireDays)
				{
					var expireDate = new Date();
					var expireDateStr = '';

					if (expireDays) {
						expireDate.setDate(expireDate.getDate()+expireDays);
						expireDateStr = ';expires=' + expireDate.toUTCString();
					}
					document.cookie = name + '=' + escape(value) + expireDateStr;
				}
				var getCookie = function(name)
				{
					if (document.cookie.length > 0) {
						var strIndex = document.cookie.indexOf(name + '=');
						if (strIndex < 0)
							return false; /* Not found */
						strIndex += name.length + 1
						var endIndex = document.cookie.indexOf(';', strIndex);
						if (endIndex < 0)
							endIndex = document.cookie.length; /* To the end */
						return unescape(document.cookie.substring(strIndex, endIndex));
					}
					return false; /* None found */
				}

				<?php echo $myid; ?>.hideMessage = function (id, msg_hash_hex)
				{
					var hideCookie = 'hideChannel' + <?php echo SERIA_Lib::toJSON($this->channel->get('id')); ?> + 'Messagedisplay' + id + 'Hash' + msg_hash_hex;
					setCookie(hideCookie, 'hide');
					var item = document.getElementById(hideCookie);
					item.style.display = 'none';
				}

				/*
				 * Code body:
				 */
				var alertMessage = function (id, title, str, msg_hash_hex) {
					var hideCookie = 'hideChannel' + <?php echo SERIA_Lib::toJSON($this->channel->get('id')); ?> + 'Messagedisplay' + id + 'Hash' + msg_hash_hex;
					if (getCookie(hideCookie) == 'hide')
						return false;
					var containment = document.createElement('div');
					var item = document.createElement('div');
					item.setAttribute('id', hideCookie);
					item.style.position = 'relative';
					item.style.overflow = 'hidden';
					var alertElement = document.createElement('h1');
					alertElement.className = 'alertTitle';
					alertElement.style.marginTop = '5px';
					alertElement.style.marginLeft = '15px';
					alertElement.style.marginRight = '15px';
					alertElement.style.marginBottom = '5px';
					alertElement.innerHTML = title;
					item.appendChild(alertElement);
					alertElement = document.createElement('p');
					alertElement.className = 'alertMessage';
					alertElement.style.marginTop = '5px';
					alertElement.style.marginLeft = '15px';
					alertElement.style.marginRight = '15px';
					alertElement.style.marginBottom = '5px';
					alertElement.innerHTML = str;
					item.appendChild(alertElement);
					closeElement = document.createElement('input');
					closeElement.setAttribute('type', 'image');
					closeElement.setAttribute('src', <?php echo SERIA_Lib::toJSON(SERIA_HTTP_ROOT.'/seria/apps/outboard/x.gif'); ?>);
					closeElement.setAttribute('title', 'Close');
					closeElement.setAttribute('value', 'Close');
					closeElement.setAttribute('onclick', '<?php echo $myid; ?>.hideMessage('+id+', "'+msg_hash_hex+'"); return false;');
					closeElement.style.position = 'absolute';
					closeElement.style.top = '5px';
					closeElement.style.right = '5px';
					closeElement.style.width = '16px';
					closeElement.style.height = '16px';
					item.appendChild(closeElement);
					item.className = 'alertMessage';
					containment.appendChild(item);
					return containment.innerHTML;
				}
				var windowLoadEvent = function () {
					windowLoadEvent = function () {
					}
					var code = '';
					var areaElement = document.createElement('div');
					areaElement.style.overflow = 'hidden';
					areaElement.style.width = '100%';
					areaElement.style.backgroundColor = '#FFDEAD';
					areaElement.style.borderBottom = '1px solid black';
					areaElement.className = 'alertBar';

					var hasAlerts = false;
					var alertCode;
					/* Insert alerts */
					<?php
						$timeNow = new SERIA_DateTimeMetaField(time());
						$timeNow = $timeNow->toDbFieldValue();
						$scheduled = SERIA_Meta::all('SERIA_AlerterSchedule')->where('start <= :start AND stop > :stop', array('start' => $timeNow, 'stop' => $timeNow));
						$shown = false;
						foreach ($scheduled as $item) {
							if (SERIA_Meta::all('SERIA_AlerterScheduleChannel')->where('scheduled = :scheduled AND channel = :channel', array('scheduled' => $item->get('id'), 'channel' => $this->channel->get('id')))->count()) {
								$message = $item->get('message');
								?>
									alertCode = alertMessage(
										<?php echo SERIA_Lib::toJSON($item->get('id')); ?>,
										<?php echo SERIA_Lib::toJSON($message->get('title')); ?>,
										<?php echo SERIA_Lib::toJSON($message->get('message'))?>,
										<?php echo SERIA_Lib::toJSON(sha1(SERIA_Lib::toJSON($message->get('title')).SERIA_Lib::toJSON($message->get('message')))); ?>
									);
									if (alertCode) {
										hasAlerts = true;
										code += alertCode;
									}
								<?php
								$shown = true;
							}
						}
						if (!$shown) {
							ob_end_clean();
							return $callbacks_code."\n/* no messages to display */";
						}
					?>

					if (hasAlerts) {
						areaElement.innerHTML = code;
						document.body.insertBefore(areaElement, document.body.firstChild);
						fireAlertMessageCallbacks();
					}
				}
				addEvent(window, 'load', function () {
					windowLoadEvent();
				});
			})();
		<?php
		return ob_get_clean();
	}
	public static function generate()
	{
		if (!file_exists(SERIA_DYN_ROOT.'/outboard'))
			mkdir(SERIA_DYN_ROOT.'/outboard', 0755);
		if (!file_exists(SERIA_DYN_ROOT.'/outboard/alerter'))
			mkdir(SERIA_DYN_ROOT.'/outboard/alerter', 0755);
		$files = array();
		$channels = SERIA_Meta::all('SERIA_AlerterChannel');
		foreach ($channels as $channel) {
			$filename = 'channel'.$channel->get('id').'.js';
			$files[] = $filename;
			$generator = new self($channel);
			$js = $generator->output();
			if (file_get_contents(SERIA_DYN_ROOT.'/outboard/alerter/'.$filename) != $js) {
				file_put_contents(SERIA_DYN_ROOT.'/outboard/alerter/'.$filename.'.tmp', $js);
				rename(SERIA_DYN_ROOT.'/outboard/alerter/'.$filename.'.tmp', SERIA_DYN_ROOT.'/outboard/alerter/'.$filename);
			}
		}
		$d = opendir(SERIA_DYN_ROOT.'/outboard/alerter');
		while (($filename = readdir($d)) !== false) {
			if (!in_array($filename, $files))
				unlink(SERIA_DYN_ROOT.'/outboard/alerter/'.$filename);
		}
	}
	public static function generateListenerCodeWithCallback($publishPointUrl, $jsLoadedCallback)
	{
		ob_start();
		?><script type="text/javascript">document.write(unescape("%3Cscript type=\"text/javascript\" src=\"<?php echo htmlspecialchars($publishPointUrl); ?>?callback=<?php echo urlencode($jsLoadedCallback); ?>&r=" + Math.floor((new Date()).getTime() / 60000) + "\" defer=\"defer\" %3E%3C/script%3E"));</script><?php
		return ob_get_clean();
	}
}
