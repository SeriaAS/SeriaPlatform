<s:gui title="{'Generate app-key'|_t}">
	<?php
	SERIA_ProxyServer::nocache();

	if (!isset($_GET['urltest'])) {
		$currentUrl = SERIA_Url::current();
		SERIA_Base::redirectTo($currentUrl->setParam('urltest', 1)->__toString());
	}
	if (isset($_GET['loginPath'])) {
		if (!SERIA_Base::user()) {
			$loginUrl = new SERIA_Url(SERIA_HTTP_ROOT);
			$loginUrl->setPath($_GET['loginPath']);
			$loginUrl->setParam('continue', SERIA_Url::current()->__toString());
			SERIA_Base::redirectTo($loginUrl->__toString());
		}
	} else
		SERIA_Base::pageRequires('login');

	$keygen = new SAPI_Autokey();

	if (isset($_POST['data'])) {
		$returnUrl = SAPI_Autokey::getReturnUrl($_POST['data']);
		$secret = $keygen->verify($_POST['data']);
		
		if ($secret) {
			/*
			 * Success, send the key!
			 */
			?>
			<form method='post' action="{{$returnUrl|htmlspecialchars}}">
				<input type='hidden' name='appKey' value="{{$secret|htmlspecialchars}}">
				<div id='autosubmit'>
					<input id='autosubmitbutton' type='submit' value='Continue'>
				</div>
				<div id='automessage'>
				</div>
			</form>
			<script>
				(function (hideDiv, messageDiv, autosubmit) {
					hideDiv.style.display = 'none';
					messageDiv.innerHTML = 'One moment, please wait...';
					autosubmit.form.submit();
				})(document.getElementById('autosubmit'), document.getElementById('automessage'), document.getElementById('autosubmitbutton'));
			</script>
			<?php
		} else {
			$retryUrl = SERIA_Meta::manifestUrl('SAPI', 'generateAppKey', array('reqUrl' => $returnUrl));
			?>
			<h1 class='legend'>{{'Generate app-key'|_t}}</h1>
			<p>{{'Sorry, an error occured. Would you like to try again?'|_t}}</p>
			<p><a href="{{$retryUrl|htmlspecialchars}}">{{'Retry'|_t}}</a> <a href="{{$returnUrl|htmlspecialchars}}">{{'Cancel'|_t}}</a></p>
			<?php
		}
	} else {
		/*
		 * This should not happen, and if it does it must be a robot.
		 */
		if (!isset($_GET['reqUrl']) || !$_GET['reqUrl'])
			throw new SERIA_Exception('reqUrl param is required.', SERIA_Exception::NOT_FOUND);

		$myServiceHostname = new SERIA_Url(SERIA_HTTP_ROOT);
		$myServiceHostname = $myServiceHostname->getHost();

		$token = $keygen->getVerificationToken($_GET['reqUrl']);
		$requestingHost = $token->getRequestingHost();
		?>
		<form method='post'>
			<input type='hidden' name='data' value="{{$token|htmlspecialchars}}">
			<div>
				<h1 class='legend'>{{'Generate app-key'|_t}}</h1>
				<p>{{'Would you like to allow %0% access to your personal profile on %1%?'|_t($requestingHost, $myServiceHostname)}}</p>
				<input type='submit' value="{{'Yes'|_t|htmlspecialchars}}"> <a href="{{$requestingUrl|htmlspecialchars}}">No</a>
			</div>
		</form>
		<?php
	}
	?>
</s:gui>