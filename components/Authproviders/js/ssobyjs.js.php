<?php

if (!defined('SERIA_HTTP_ROOT')) {
	require_once(dirname(__FILE__).'/../../../main.php');
	SERIA_Template::disable();
}

$supportedSiteTypes = array('test', 'drupal', 'seriaplatform');

if (isset($_GET['siteType']))
	$siteType = $_GET['siteType'];

if (isset($siteType)) {
	if (!in_array($siteType, $supportedSiteTypes))
		die('Site type ('.$siteType.') is not supported. Sorry.');
} else {
	die('Site type (siteType) must be specified');
}

SERIA_ProxyServer::publicCache(604800);

$jsonpId = sha1(SERIA_HTTP_ROOT).mt_rand().mt_rand();
$jsonpLoginInfo = 'jsonp_login_info_'.$jsonpId;

?>

globalSsoByJsRequesterData = [];

(function () {
	var jsonpRequestCounter = 0;
	var jsonpRequests = {};
	var lwJsonpRequester_script_node = function(url, text)
	{
		var scriptTag = document.createElement('script');
		var scriptText = document.createTextNode(text);
		var debug = '';
		if (url)
			debug += 'url='+url+': ';
		debug += text;
		scriptTag.setAttribute('type', 'text/javascript');
		if (url)
			scriptTag.setAttribute('src', url);
		if (text)
			scriptTag.appendChild(scriptText);
		document.body.appendChild(scriptTag);
	};
	var lwJsonpRequester = function (url, callback)
	{
		var callbackName = 'ssobyjsjsonp_request_'+jsonpRequestCounter;
		var callbackText = 'function '+callbackName+'(object) { globalSsoByJsRequesterData['+jsonpRequestCounter+'](object); globalSsoByJsRequesterData['+jsonpRequestCounter+'] = false; }';
		globalSsoByJsRequesterData[jsonpRequestCounter] = callback;

		jsonpRequestCounter++;

		var delimiter = '?';
		if (url.indexOf('?') >= 0)
			delimiter = '&';
		url = url + delimiter + 'jsonp='+encodeURIComponent(callbackName);
		lwJsonpRequester_script_node(false, callbackText);
		lwJsonpRequester_script_node(url, '');
	};
	var slashifyBaseUri = function(baseUri)
	{
		if (baseUri.charAt(baseUri.length - 1) != '/')
			return baseUri + '/';
		else
			return baseUri;
	};

	var baseUri = slashifyBaseUri(<?php echo SERIA_Lib::toJSON(SERIA_HTTP_ROOT); ?>);

	var requestLoginXml = function (callback)
	{
		lwJsonpRequester(baseUri + 'seria/api/?apiPath=SAPI_SSOByJavascript/ssoCheck&apiReturn=jsonp', callback);
	};
	var trySso = function ()
	{
		requestLoginXml(function (data) {
			<?php
			switch($siteType) {
				case 'test':
				?>
					if (data.loggedIn)
						alert(data.userXml);
					else
						alert('Not logged in');
				<?php
				break;
				case 'drupal':
				?>
					if (data.loggedIn) {
						jQuery.ajax(
							{
								'url': '/seriaauth/ssoidentity',
								'async': true,
								'data': {
									'roamauthurl': data.userXml,
									'userChange': data.userChange
								},
								'type': 'POST',
								'dataType': 'text',
								'success': function (data, textStatus, jqXHR) {
									if (typeof JSON === 'object' && typeof JSON.parse === 'function')
										data = JSON.parse(data);
									else
										eval('data = '+data+';');
									if (data.reload)
										location.reload(true);
								}
							}
						);
					} else {
						jQuery.ajax(
							{
								'url': '/seriaauth/ssoidentity',
								'async': true,
								'data': {
									'roamauthurl': '',
									'userChange': data.userChange
								},
								'type': 'POST',
								'dataType': 'text',
								'success': function (data, textStatus, jqXHR) {
									if (typeof JSON === 'object' && typeof JSON.parse === 'function')
										data = JSON.parse(data);
									else
										eval('data = '+data+';');
									if (data.reload)
										location.reload(true);
								}
							}
						);
					}
				<?php
				break;
				case 'seriaplatform':
				?>
					if (data.loggedIn) {
						jQuery.ajax(
							{
								'url': '/?route=seria/Authproviders/metaSsoIdentity',
								'async': true,
								'data': {
									'roamauthurl': data.userXml,
									'userChange': data.userChange
								},
								'type': 'POST',
								'dataType': 'text',
								'success': function (data, textStatus, jqXHR) {
									if (typeof JSON === 'object' && typeof JSON.parse === 'function')
										data = JSON.parse(data);
									else
										eval('data = '+data+';');
									if (data.reload)
										location.reload(true);
								}
							}
						);
					} else {
						jQuery.ajax(
							{
								'url': '/?route=seria/Authproviders/metaSsoIdentity',
								'async': true,
								'data': {
									'roamauthurl': '',
									'userChange': data.userChange
								},
								'type': 'POST',
								'dataType': 'text',
								'success': function (data, textStatus, jqXHR) {
									if (typeof JSON === 'object' && typeof JSON.parse === 'function')
										data = JSON.parse(data);
									else
										eval('data = '+data+';');
									if (data.reload)
										location.reload(true);
								}
							}
						);
					}
				<?php
				break;
			}
			?>
		});
	};

	if (window.addEventListener)
		window.addEventListener('load', trySso, false);
	else if (window.attachEvent)
		window.attachEvent('onload', trySso);
	else
		trySso();
})();
