<?php

class JEP_EsiIncludedHtmlTokenCompiler extends OR_EsiHtmlTokenCompiler
{
	public static function paramsHtmlEntitiesDecode($params)
	{
		$p = array();
		foreach ($params as $name => $value)
			$p[$name] = html_entity_decode($value);
		return $p;
	}
	public function includeTag($params)
	{
		throw new SERIA_Exception('Recursive esi:include is not allowed!');
	}
	public function ajaxpostactionTag($params)
	{
		$params = self::paramsHtmlEntitiesDecode($params);
		if (isset($params['session']) && $params['session'])
			$_SESSION['ESI_SESSION_REQUIRED'] = true;
		$url = $params['url'];
		$proxyParams = array();
		SERIA_Url::parse_str($params['data'], $proxyParams);
		$jscall = $params['jscall'];
		if (isset($_POST['proxy_post_url']) && $_POST['proxy_post_url'] == sha1($url)) {
			SERIA_ProxyServer::noCache();
			$post = $_POST;
			unset($post['proxy_post_url']);
			$post['proxy_post_origin_ip'] = $_SERVER['REMOTE_ADDR'];
			if (session_id())
				$post['proxy_post_session_idhash'] = sha1(session_id());
			try {
				$browser = new SERIA_WebBrowser();
				$browser->navigateTo($url, $post);
				$data = $browser->fetchAll();
				if (isset($browser->responseHeaders['Content-Type']))
					$contentType = $browser->responseHeaders['Content-Type'];
				else
					$contentType = 'text/plain';
				self::overrideContent($contentType, $data);
			} catch (SERIA_Exception $e) {
				SERIA_Template::disable();
				header('HTML/1.1 500 Internal Server Error');
				self::overrideContent('text/plain', $e->getMessage());
			}
		}
		$ajax = array(
			'url' => SERIA_Url::current()->__toString(),
			'async' => false,
			'dataType' => 'text',
			'type' => 'POST',
		);
		ob_start();
		?>
			<script type='text/javascript'>
				<!--
					var <?php echo $jscall; ?> = function (postData, asyncCallbacks) {
						var params = <?php echo ($proxyParams ? SERIA_Lib::toJSON($proxyParams) : '{}'); ?>;
						for (var i in postData) {
							params[i] = postData[i];
						}
						postData = params;
						postData.proxy_post_url = <?php echo SERIA_Lib::toJSON(sha1($url)); ?>;
						var ajaxOptions = <?php echo SERIA_Lib::toJSON($ajax); ?>;
						ajaxOptions.data = postData;
						if (asyncCallbacks) {
							ajaxOptions.async = true;
							ajaxOptions.success = ajaxOptions.success;
							ajaxOptions.error = ajaxOptions.error;
						}
						var httpRequest = jQuery.ajax(ajaxOptions);
						return httpRequest;
					}
				-->
			</script>
		<?php
		$code = ob_get_clean();
		return 'echo '.var_export($code, true).';';
	}
	public static function recursiveCompile($html)
	{
		$compiler = new self('esi');
		$code = $compiler->compile($html);

		/* Removing anything that cause php to activate */
		$html = str_replace(array('<'.'?', '?'.'>'), array('[[[?', '?]]]'), $html);

		ob_start();
		eval('?>'.$code);
		$output = ob_get_clean();
		
		/* And undoing */
		return str_replace(array('[[[?', '?]]]'), array('<'.'?', '?'.'>'), $output);
	}

	/*
	 * Works around bugs in SERIA_Template.
	 */
	protected static function overrideContent($contentType, $content)
	{
		SERIA_Template::disable();
		header('Content-Type: '.$contentType);
		while (ob_end_clean());
		die($content);
	}
}