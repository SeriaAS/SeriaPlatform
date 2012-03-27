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

		protected function putCache($key, $c, &$cache, $cachetime=90000) {
			if ($_POST && sizeof($_POST) > 0) return false;
			$cache->set($key, $c, $cachetime);
		}

		function getCache($key, &$cache) {
			if ($_POST && sizeof($_POST) > 0) return false;
			return $cache->get($key);
		}

		// hook for urls
		function router($url)
		{
			if ($url[0] != '/') $url = '/'.$url;

			$nocache = '/nocache/';
			$len = strlen($nocache);
			if (strlen($url) > $len && substr($url, 0, $len) == $nocache) {
				$nocache = true;
				$url = substr($url, $len - 1);
				SERIA_ProxyServer::noCache();
			} else
				$nocache = false;

			$class = new stdClass();
			$class->url = $url;
			$class->nocache = $nocache;
			SERIA_Hooks::dispatch('esiFrontend_beforeLoading', $class);
			$url = $class->url;

			if (!$nocache) {
				$cache = $this->getCacheObject();
				$cacheKey = md5($url);
				$currentCache = $this->getCache($cacheKey, $cache);
				if (!$currentCache)
					$currentCache = array();
			} else
				$currentCache = array();

			$sentHeaders = array();
			$headersToCache = array();

			//TODO: Only supporting one language, removing additional languages
			$langString = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
			$langs = explode(',', $langString);
			$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $langs[0];

			if (isset($currentCache['vary']) && $currentCache['vary']) {
				$ident = '';
				foreach ($currentCache["vary"] as $vary) {
					$ident .= $vary.'_'.strtolower($_SERVER['HTTP_'.str_replace('-','_',strtoupper($vary))]).'_';
				}
			} else if (isset($currentCache['_']) && $currentCache['_'])
				$ident = '_';
			else
				$ident = null;
			if ($ident !== null) {
				$currentHeaders = $currentCache[$ident]['headers'];
				$c = $currentCache[$ident]['content'];
				if (isset($currentCache[$ident]['code']) && $currentCache[$ident]['code']) {
					header($_SERVER['SERVER_PROTOCOL'].' '.$currentCache[$ident]['code'].' '.$currentCache[$ident]['codeString']);
					header('Status: '.$currentCache[$ident]['code'].' '.$currentCache[$ident]['codeString']);
				}
				if ($c && isset($currentCache[$ident]['cacheDesc'])) {
					$cacheDesc = $currentCache[$ident]['cacheDesc'];
					$cacheAge = time() - $cacheDesc['ts'];
					SERIA_Template::headPrepend('cache_control_info', "<!-- CONTENT CACHED (age=".$cacheAge."):\n".$cacheDesc['debug'].' -->');
				} else
					$cacheDesc = null;
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

				/*
				 * Generate our own XFF.
				 */
				$xff = $_SERVER['REMOTE_ADDR'];

				/*
				 * Seria Platform oddity: REMOTE_ADDR overwritten by the X-Forwarded-For value.
				 * Hacking around r2696 of seria/main.php:
				 */
				if (isset($_SERVER['HTTP_X_FORWARDED_BY']) && $_SERVER['HTTP_X_FORWARDED_BY'])
					$xff = $_SERVER['HTTP_X_FORWARDED_BY'];

				/*
				 * Any existing XFF?
				 */
				if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'])
					$orig_xff = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
				else
					$orig_xff = '';
				if ($orig_xff)
					$xff = $orig_xff.', '.$xff;

				$b->customRequestHeaders['X-Forwarded-For'] = $xff;

				$b->navigateTo(ESIFRONTEND_BACKEND_HTTPROOT.$url, $_POST, ESIFRONTEND_BACKEND_IP, ESIFRONTEND_BACKEND_PORT);
				$esiMimeTypes = array(
					'text/xml' => 1,
					'text/html' => 1,
					'text/javascript' => 1,
					'application/xml' => 1,
				);

				$c = $b->fetch(); // reading some data to fetch headers

				$hdrs = array('Expires', 'Date', 'Cache-Control');
				$hdrs = array_flip($hdrs);
				foreach ($hdrs as $name => &$value)
					$value = isset($b->responseHeaders[$name]) && $b->responseHeaders[$name];
				unset($value);
				$cacheControlText = '';
				if (($hdrs['Date'] && $hdrs['Expires']) || $hdrs['Cache-Control']) {
					$expires_ttl = false;
					if ($hdrs['Date'] && $hdrs['Expires']) {
						$cacheControlText .= 'HTTP/1.0 Date&Expires: date="'.$b->responseHeaders['Date'].'", expires="'.$b->responseHeaders['Expires'].'"'."\n";
						$date = new DateTime($b->responseHeaders['Date']);
						$expires = new DateTime($b->responseHeaders['Expires']);

						/*
						 * Compat with PHP 5.2
						 */
						if ((PHP_MAJOR_VERSION == 5 && PHP_MINOR_VERSION >= 3) || PHP_MAJOR_VERSION > 5) {
							$date = $date->getTimestamp();
							$expires = $expires->getTimestamp();
						} else {
							$date = strtotime($date->format('Y-m-d H:i:s'));
							$expires = strtotime($expires->format('Y-m-d H:i:s'));
						}
						$now = time();
						$cacheControlText .= 'Timestamp: now='.$now.', date='.$date.', expires='.$expires."\n";
						if ($expires >= $date) {
							$ttl = $expires - $now;
							if ($ttl < 0)
								$ttl = 0;
							$expires_ttl = $ttl;
							$cacheControlText .= 'HTTP/1.0 allows caching with ttl='.$ttl."\n";
						} else {
							$cacheControlText .= 'HTTP/1.0 disallows caching.'."\n";
							$ttl = false;
						}
					}
					if ($hdrs['Cache-Control']) {
						$cacheControl = $b->responseHeaders['Cache-Control'];
						$cacheControlText .= 'Backend Cache-Control (overrides HTTP/1.0): '.$cacheControl."\n";
						$cacheControl = new SERIA_CacheControl($cacheControl);
						if (!$cacheControl->noCache() && !$cacheControl->noStore()) {
							if ($cacheControl->isPublic()) {
								$ttl = $cacheControl->getPublicMaxAge();
								if ($ttl === null) {
									if ($expires_ttl)
										$ttl = $expires_ttl;
									else
										$ttl = 86400;
								}
								$cacheControlText .= 'HTTP/1.1 allows public caching with ttl='.$ttl."\n";
							} else if ($cacheControl->isPrivate()) {
								$cacheControlText .= 'HTTP/1.1 disallows public caching, but allows private. Frontend can\'t cache!'."\n";
								$ttl = false;
							} else {
								$cacheControlText .= 'HTTP/1.1 does not allow or deny caching!'."\n";
								$ttl = false;
							}
						} else {
							$cacheControlText .= 'HTTP/1.1 disallows caching.'."\n";
							$ttl = false;
						}
					}
				} else {
					$cacheControlText .= "No HTTP/1.0 or HTTP/1.1 cache headers, not cacheable!\n";
					$ttl = false;
				}
				$cacheable = false;
				if ($ttl || ($ttl === 0 && defined('ESIFRONTEND_MAIN_CONTENT_TTL') && ESIFRONTEND_MAIN_CONTENT_TTL)) {
					if (defined('ESIFRONTEND_MAIN_CONTENT_TTL') && ESIFRONTEND_MAIN_CONTENT_TTL) {
						$cacheControlText .= 'Cache ttl override (ESIFRONTEND_MAIN_CONTENT_TTL): '.$ttl.'=>'.ESIFRONTEND_MAIN_CONTENT_TTL."\n";
						$ttl = ESIFRONTEND_MAIN_CONTENT_TTL;
					}
					$cacheControlText .= 'Final frontend caching decision: YES cacheable with ttl='.$ttl."\n";
					$cacheable = true;
				} else if ($ttl !== false)
					$cacheControlText .= 'Final frontend caching decision: NO not cacheable with ttl='.$ttl."\n";
				else 
					$cacheControlText .= "Final frontend caching decision: NO not cacheable\n";
				SERIA_Template::headPrepend('cache_control_info', "<!-- CONTENT NOT CACHED:\n".$cacheControlText.' -->');
				if ($b->responseHeaders['Location']) {
					$redirectTo = str_replace(ESIFRONTEND_BACKEND_HTTPROOT, 'http://'.$_SERVER['HTTP_HOST'], $b->responseHeaders['Location']);
					SERIA_Base::redirectTo($redirectTo);
				}
				$cacheDesc = array(
					'cacheable' => $cacheable,
					'ts' => time(),
					'ttl' => $ttl,
					'debug' => $cacheControlText
				);
				$cacheAge = 0;
				

				$headersWhitelist = array("vary" => true, "content-description" => true, "content-type" => true, "content-disposition" => true, "content-transfer-encoding" => true);

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

				if ($b->responseCode != 200) {
					header($_SERVER['SERVER_PROTOCOL'].' '.$b->responseCode.' '.$b->responseString);
					header('Status: '.$b->responseCode.' '.$b->responseString);
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

				if (!$nocache) {
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
	
					$skipCache = !$cacheDesc['cacheable'];
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
						$currentCache[$ident] = array('content' => $c, 'headers' => $headersToCache, 'code' => $b->responseCode, 'codeString' => $b->responseString, 'cacheDesc' => $cacheDesc);
						if ($b->responseCode == 200) {
							$cacheTime = $cacheDesc['ttl'];
							if (defined('ESIFRONTEND_MAIN_CONTENT_TTL'))
								$cacheTime = ESIFRONTEND_MAIN_CONTENT_TTL;
							if ($cacheTime > 0)
								$this->putCache($cacheKey, $currentCache, $cache, $cacheTime);
						} else
							$this->putCache($cacheKey, $currentCache, $cache, 60); /* Cache 1 minute on failure */
					}
				}
			} else {

				foreach ($currentHeaders as $key => $value) {
					if (strtolower($key) == 'content-type' && strpos($value, 'text/html') !== false) $passthru = false;
					if (strtolower($key) == 'cache-control' || strtolower($key) == 'expires')
						continue;
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
				if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS) {
					if ($cacheDesc !== null) {
						if ($cacheDesc['cacheable']) {
							$ttl = $cacheDesc['ttl'] - $cacheAge;
							if ($ttl < 0)
								$ttl = 0;
							SERIA_ProxyServer::publicCache($ttl);
						} else
							SERIA_ProxyServer::noCache();
					}
				}

				$d = new stdClass();
				$d->c = $c;
				$result = SERIA_Hooks::dispatch('esiFrontend_contentLoaded', $d);
				$c = $d->c;
	
				$compiler = new OR_EsiHtmlTokenCompiler($c, "esi");
				echo $compiler->__toString();
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

