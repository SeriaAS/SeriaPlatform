<?php
	//TODO: Could this be used to hack NDLA if we f.ex put ESI code in the search field?

	class SERIA_EsiFrontendApplication extends SERIA_Application
	{
		protected $cache = null;

		function getId() { return 'seria_esiFrontend'; }
		function getHttpPath() { return SERIA_HTTP_ROOT.'/seria/apps/esiFrontend'; }
		function getInstallationPath() { return dirname(dirname(__FILE__)); }
		function getName() { return _t('Seria ESI Frontend'); }

		function __construct()
		{
		}

		// Add event listeners and hook into wherever
		function embed()
		{
			SERIA_Hooks::listen(SERIA_GuiHooks::EMBED, array($this, 'guiEmbed'));
			SERIA_Hooks::listen('seria_router', array($this, 'router'), -100);
		}

		/**
		 *
		 * Returns the cache-object used in this instance of the esi-application.
		 * @return SERIA_ICache
		 */
		public function getCacheObject()
		{
			if ($this->cache === null)
				$this->cache = new SERIA_Cache('Esi');
			return $this->cache;
		}
		/**
		 *
		 * Delete all ESI-cache.
		 */
		public function deleteAllCache()
		{
			$this->getCacheObject()->deleteAll();
		}

		function putCache($key, $c, &$cache) {
			if ($_POST && sizeof($_POST) > 0) return false;
			$cache->set($key, $c, 90000); // cache for 25 hours
		}

		function getCache($key, &$cache) {
			if ($_POST && sizeof($_POST) > 0) return false;
			return $cache->get($key);
		}

		// hook for urls
		function router($url)
		{
			if ($url[0] != '/') $url = '/'.$url;

			$class = new stdClass();
			$class->url = $url;
			SERIA_Hooks::dispatch('esiFrontend_beforeLoading', $class);
			$url = $class->url;

			$cache = $this->getCacheObject();
			
			$cacheKey = md5($url);

			$currentCache = $this->getCache($cacheKey, $cache);
			if (!$currentCache) $currentCache = array();

			$sentHeaders = array();
			$headersToCache = array();

			//TODO: Only supporting one language, removing additional languages
			$langString = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$langs = explode(',', $langString);
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $langs[0];

			if ($currentCache['vary']) {
				$ident = '';
				foreach ($currentCache["vary"] as $vary) {
					$ident .= $vary.'_'.strtolower($_SERVER['HTTP_'.str_replace('-','_',strtoupper($vary))]).'_';
				}
				$currentHeaders = $currentCache[$ident]['headers'];
				$c = $currentCache[$ident]['content'];
			} else {
				$currentHeaders = $currentCache['_']['headers'];
				$c = $currentCache["_"]['content'];
			}

			$passthru = true;

			if (ESIFRONTEND_CLOSE_SESSION) session_write_close();
			if (!$c) {
				// if url exists, then we start building the page
				$b = new SERIA_WebBrowser();

				$b->followRedirect = false;
				$b->acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
				$sentHeaders["Accept-Language"] = $b->acceptLanguage;

				if ($_SERVER['X_SERIA_HTTPS']) {
					$b->customRequestHeaders = array('X-SERIA-HTTPS' => '1');
				}

				$b->navigateTo(ESIFRONTEND_BACKEND_HTTPROOT.$url, $_POST, ESIFRONTEND_BACKEND_IP, ESIFRONTEND_BACKEND_PORT);
				$esiMimeTypes = array(
					'text/xml' => 1,
					'text/html' => 1,
					'text/javascript' => 1,
					'application/xml' => 1,
				);

				$c = $b->fetch(); // reading some data to fetch headers

				if ($b->responseHeaders['Location']) {
					$redirectTo = str_replace(ESIFRONTEND_BACKEND_HTTPROOT, 'http://'.$_SERVER['HTTP_HOST'], $b->responseHeaders['Location']);
					header("Location: ".$redirectTo);
					die();
				}


				$headersWhitelist = array("vary" => true, "content-description" => true, "content-type" => true, "content-disposition" => true, "content-transfer-encoding" => true, "content-length" => true);

				foreach ($b->responseHeaders as $key => $value) {
					if ($headersWhitelist[strtolower($key)]) {
						$headersToCache[$key] = $value;
						if (is_array($value)) {
							foreach ($value as $subKey => $subValue) {
								header($key.": ".$subValue, false);
							}
						} else {
							header($key.": ".$value);
						}
					}
				}


				if (!$esiMimeTypes[current(explode(';', strtolower($b->responseHeaders['Content-Type'])))]) {
					SERIA_Template::disable();
					echo $c;
					while ($c = $b->fetch()) {
						echo $c;
						ob_flush();
					}
					die();
				}


                                try {
					while ($chunk = $b->fetch()) {
 	                                       $c .= $chunk;
					}
                                } catch (SERIA_Exception $e) {
                                        SERIA_Base::displayErrorPage('500', 'No response from backend');
                                        die();
                                }

				$contentType = explode(';' , strtolower($b->responseHeaders["Content-Type"]));
				$mimeType = $contentType[0];

				if ($mimeType == 'text/html') $passthru = false;

/*
				if (strpos(strtolower($b->responseHeaders["Content-Type"]), "text/html") === false) {
					SERIA_Template::disable();

					$cachableMimeTypes = array(
						'text/javascript',
						'application/xml',
					);

					if (!in_array($mimeType, $cachableMimeTypes)) {
						die($c);
					}
				}
*/

				$varyString = $b->responseHeaders['Vary'];
				if ($varyString) {
					$varys = explode(",", $varyString);
					$currentCache["vary"] = $varys;
					$ident = "";
					foreach ($varys as $vary) {
						$vary = trim($vary);
						$ident .= $vary."_".strtolower($sentHeaders[$vary])."_";
					}
				} else {
					$ident = "_";
				}

                                $skipCache = false;
                                if (defined('ESIFRONTEND_SKIP_CACHE')) {
                                        $skipUrls = explode(',', ESIFRONTEND_SKIP_CACHE);
                                        foreach ($skipUrls as $skipUrl) {
                                                if (strpos($url, $skipUrl) !== false) {
                                                        $skipCache = true;
                                                        break;
                                                }
                                        }
                                }

                                if (!$skipCache) {
                                        $currentCache[$ident] = array('content' => $c, 'headers' => $headersToCache);
                                        $this->putCache($cacheKey, $currentCache, $cache);
                                }

			} else {

				foreach ($currentHeaders as $key => $value) {
					if (strtolower($key) == 'content-type' && strpos($value, 'text/html') !== false) $passthru = false;
					if (is_array($value)) {
						foreach ($value as $subKey => $subValue) {
							header($key.": ".$subValue, false);
						}
					} else {
						header($key.": ".$value);
					}
				}

			}

			if ($passthru) {
				SERIA_Template::disable();
				die($c);
			} else {
				$d = new stdClass();
				$d->c = $c;
				$result = SERIA_Hooks::dispatch('esiFrontend_contentLoaded', $d);
				$c = $d->c;
	
				$compiler = new OR_EsiHtmlTokenCompiler("esi");
				$c = str_replace(array('<'.'?', '?'.'>'), array('[[[?', '?]]]'), $c);
				ob_start();
				eval('?>'.$compiler->compile($c));
				$c = ob_get_contents();
				ob_end_clean();
				$c = str_replace(array('[[[?', '?]]]'), array('<'.'?', '?'.'>'), $c);

				echo $c;
			}
			die();
		}

		// hook for adding icon to the user interface
		function guiEmbed($gui)
		{
			$gui->addMenuItem('esiFrontend', $this->getName(), _t('Seria ESI Frontend is an application that parses ESI include tags for a backend website. It completely hides the backend website and acts as a proxy server.'), SERIA_HTTP_ROOT.'/seria/apps/esiFrontend/', SERIA_HTTP_ROOT.'/seria/apps/esiFrontend/icon.png', 100);
			$gui->addMenuItem('esiFrontend/configuration', _t('Configuration'), _t('Configure external services and their access level for authentication and the AJAX-proxy.'), SERIA_HTTP_ROOT.'/seria/apps/esiFrontend/configuration/');
			$gui->addMenuItem('esiFrontend/statistics', _t('Statistics'), _t('Various statistics for your backend/frontend-configuration.'), SERIA_HTTP_ROOT.'/seria/apps/esiFrontend/statistics/');
		}
	}

