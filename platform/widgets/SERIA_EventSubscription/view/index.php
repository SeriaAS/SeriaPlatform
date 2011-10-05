<?php

/*
 * Ok, this is a bit ugly. But it certainly works as for identifying which form on a page that was submitted.
 */

SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/view/index3.css');

$myViewNumber = ++SERIA_EventSubscriptionWidget::$widgetViewCount;

$article = $this->getNamedObject();

if ($this->get('enabled')) {
	$enabled = true;
	if ($this->get('participants') && $this->countAllSubscribers() >= $this->get('participants'))
		$enabled = false;
	if ($this->get('deadline') && $this->get('deadline') <= time())
		$enabled = false;
} else
	$enabled = false;

$subscr_form = $this->getForm('subscribe');
$subscr_form->viewNumber = $myViewNumber; /* Note */

if ($subscr_form === false)
	return;

if (sizeof($_POST) && isset($_POST['widget_id']) && $_POST['widget_id'] == $this->getId()) {
	while (substr($_SERVER['REQUEST_URI'], 0, 2) == '//')
		$_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], 1);
	$url = $this->get('redirect_url');
	if ($url === null)
		$url = SERIA_HTTP_ROOT.$_SERVER['REQUEST_URI'];
	$url_p = parse_url($url);
	parse_str($url_p['query'], $q);
	$url_p['query'] = $q;
	try {
		if ($subscr_form->receive($_POST) !== false) {
			/*
			 * Add $_GET parameter for subscribe ok.
			 */

			/* Redirect url */
			$url_p['query']['event_subscribed'] = $_POST['widget_id'];
			$url_p['query'] = http_build_query($url_p['query']);
			$url = $url_p['scheme'].'://'.$url_p['host'].(isset($url_p['port']) ? ':'.$url_p['port'] : '').$url_p['path'].'?'.$url_p['query'].(isset($url_p['fragment']) ? '#'.$url_p['fragment'] : '');

			/*
			 * Try to detect website hostname..
			 */
			$httproot = SERIA_HTTP_ROOT;
			if (substr($httproot, 0, 7) == 'http://')
				$host = substr($httproot, 7);
			else if (substr($httproot, 0, 8) == 'https://')
				$host = substr($httproot, 8);
			else
				throw new Exception('Unable to understand SERIA_HTTP_ROOT');
			$pos = strpos($host, '/');
			if ($pos !== false)
				$host = substr($host, 0, $pos);
			if (strpos($host, 'www.') === 0)
				$host = substr($host, 4);
			if ($pos === 0 || strlen($host) == 0)
				throw new Exception('Unable to understand SERIA_HTTP_ROOT');

			/* Send email */
			$templateName = 'email_subscribed.php';
			$rel_filename = 'widgets/'.$this->getWidgetDirname().'/templates/'.$templateName;
			$filename = SERIA_ROOT.'/'.$rel_filename;
			if (!file_exists($filename))
				$filename = SERIA_ROOT.'/seria/platform/'.$rel_filename;
			$tpl = new SERIA_MetaTemplate();
			$tpl->addVariable('title', $article->get('title'));
			$tpl->addVariable('event', $this);
			$tpl->addVariable('article', $article);
			$tpl->addVariable('link', $url);
			$email_cont = $tpl->parse($filename);
			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'Content-Type: text/html; charset=utf-8' . "\r\n";
			$headers .= 'Content-Transfer-Encoding: base64'."\r\n";
			$headers .= 'From: <donotreply@'.$host.">\r\n";
			$to = $subscr_form->get('email');
			$title = _t('You have been subscribed to: %TITLE%', array('TITLE' => $article->get('title')));
			mail($to, encode_header_value($title, "utf-8"), imap_binary($email_cont), $headers);

			/* Redirect */
			header('Location: '.$url);
			die();
		} else if ($_POST['form_viewNumber'] == $myViewNumber) /* Which form was submitted? */
			$errorMode = true;
	} catch (Exception $e) {
		$script = new SERIA_BMLScript(
'
		if (typeof(global_seria_event_subscription_widget_silencer) == \'undefined\') {
			var global_seria_event_subscription_widget_silencer = \'silenced\';
			alert(\''.htmlspecialchars(_t('Subscription to event failed. (%EVENT%: %ERROR%).', array('ERROR' => $e->getMessage(), 'EVENT' => $article->get('title')))).'\');
		}
', 'text/javascript'
		);
		echo $script->output();
	}
} else
	$errorMode = false;

if (!isset($_GET['event_subscribed']) || $_GET['event_subscribed'] != $this->getId()) {
	if ($enabled) {
		SERIA_Template::cssInclude(SERIA_HTTP_ROOT.'/seria/platform/widgets/SERIA_EventSubscription/css/style.css');

		$id = 'subscr_f_ids'.$myViewNumber;

		$url = new SERIA_Url($this->get('redirect_url'));
		$url->setFragment($id);
		$url = $url->__toString();
		?>
		<script type='text/javascript'>
			<!--
				<?php $jsRef = mt_rand().mt_rand(); ?>
				function SubscribeFormSwitch_<?php echo $jsRef.$id; ?>()
				{
					var button_obj = document.getElementById('SubscribeFormButton_<?php echo $id; ?>');
					var obj = document.getElementById('<?php echo $id ?>');

					if (obj.style.display == 'none') {
						button_obj.setAttribute('class', 'subscribe_form_switch_off');
						obj.style.display = 'block';
					} else {
						button_obj.setAttribute('class', 'subscribe_form_switch_on');
						obj.style.display = 'none';
					}
				}
			-->
		</script>
		<div>
			<button class='subscribe_form_switch_on' id='SubscribeFormButton_<?php echo $id; ?>' type='button' onclick="SubscribeFormSwitch_<?php echo $jsRef.$id; ?>();"><?php echo htmlspecialchars(_t("Subscribe")); ?></button>
		</div>
		<div class='subscribe_form_switch' id='<?php echo $id; ?>' style='display: <?php echo ($errorMode ? 'block' : 'none'); ?>;'>
			<div class='subscribe_form_container'>
				<?php echo $subscr_form->output(SERIA_ROOT.'/seria/platform/templates/seria/special/displayTableForm.php', array('action' => $url)); ?>
				<script type='text/javascript'>
					<!--
						(function () {
							var getFirstChild = function (element)
							{
								for (var i = 0; i < element.childNodes.length; i++) {
									if (element.childNodes[i].nodeType == 1)
										return element.childNodes[i];
								}
								return null;
							}

							/*
							 * The captcha-input field will be moved up to the captcha display field.
							 * They should be grouped together.
							 * Also show a text guiding the users to the input field.
							 */
							var captchaInputs = document.getElementsByName('captcha');
							var captchaInput = false;

							for (var i = 0; i < captchaInputs.length; i++) {
								if (!captchaInputs[i].captcha_marked) {
									captchaInputs[i].captcha_marked = true;
									captchaInput = captchaInputs[i];
									break;
								}
							}
							if (!captchaInput)
								return;

							var ciFormRow = captchaInput.parentNode;
							var ciFormRowClass = /formfield/;
							var ie7 = false;
							while (!ciFormRowClass.test(ciFormRow.className) && ciFormRow.tagName.toLowerCase() != 'tr')
								ciFormRow = ciFormRow.parentNode;
							if (ciFormRow.tagName.toLowerCase() == 'tr')
								ie7 = true;
							var displayRow = ciFormRow.previousSibling;

							if (ie7) {
								while (displayRow && (displayRow.nodeType != 1 || displayRow.tagName.toLowerCase() != 'tr'))
									displayRow = displayRow.previousSibling;
							}
							while (displayRow && displayRow.nodeType != 1)
								displayRow = displayRow.previousSibling;
							if (displayRow) {
								var classMatch = /field/;
								var labelClassMatch = /label/;
								var displayContainer = displayRow;
								while (true) {
									displayContainer = getFirstChild(displayContainer);
									if (ie7) {
										while (displayContainer.tagName.toLowerCase() != 'td')
											displayContainer = displayContainer.nextSibling;
									}
									if ((!ie7 && labelClassMatch.test(displayContainer.className)) || displayContainer.tagName.toLowerCase() == 'td') {
										do {
											displayContainer = displayContainer.nextSibling;
										} while (displayContainer.nodeType != 1 ||
										         (!classMatch.test(displayContainer.className) &&
												  displayContainer.tagName.toLowerCase() != 'td'));
										var tdElement = getFirstChild(displayContainer);
										if (tdElement.tagName.toLowerCase() == 'td')
											displayContainer = tdElement;
										captchaInput.parentNode.removeChild(captchaInput);
										displayContainer.appendChild(captchaInput);
										break;
									}
								}
							}
							captchaInput.originalColor = captchaInput.style.color;
							captchaInput.style.color = 'gray';
							captchaInput.value = <?php echo SERIA_Lib::toJSON(_t('Type the numbers above here...')); ?>;
							captchaInput.onclick = function () {
								captchaInput.onclick = function () {
									return true;
								}
								captchaInput.value = '';
								captchaInput.style.color = captchaInput.originalColor;
								return true;
							}
						})();
					-->
				</script>
			</div>
		</div>
		<?php
	}
} else {
	?>
	<fieldset class='subscriptionMessage'>
		<legend><?php echo htmlspecialchars(_t('Subscribed')); ?></legend>
		<p><?php echo htmlspecialchars(_t('Subscribed to this event.')); ?></p>
		<?php
			if (!isset($GLOBALS['subscriptionWidgetAnnouncedOk'])) {
				$GLOBALS['subscriptionWidgetAnnouncedOk'] = true;
				?>
					<script type='text/javascript'>
						<!--
							$(document).ready(function () {
								alert(<?php echo SERIA_Lib::toJSON(_t('You have been subscribed to this event.')); ?>);
							});
						-->
					</script>
				<?php
			}
		?>
	</fieldset>
	<?php
}

?>
