<?php
	class OR_EsiHtmlTokenCompiler extends OR_HtmlTokenCompiler {

		/**
		 *
		 * Se the compileLoopEndHandler function.
		 * @var array
		 */
		protected $nodes = array();

		/**
		 *
		 * Test if the string is a number.
		 * @param unknown_type $str
		 */
		protected static function isPartialIntStr($str)
		{
			$allowed = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
			$chars = str_split($str);
			if (!$chars)
				return false;
			$count = 0;
			foreach ($chars as $c) {
				if (!in_array($c, $allowed)) {
					if ($count)
						return $count;
					else
						return false;
				}
				$count++;
			}
			return $count;
		}

		/**
		 * Get the integer and remove it from the string
		 */
		protected static function extractInteger(&$str)
		{
			return $int;
		}

		protected function textNode($text)
		{
			$this->nodes[] = array('text', $text);
		}
		protected function includeTag($params)
		{
			if (!$params["src"])
				throw new SERIA_Exception("src attribute is required in ESI include tag");
			if (strpos($params["src"], "http://") !== 0 && strpos($params["src"], "https://") !== 0)
				throw new SERIA_Exception("Security alert in ESI include tag");
			if (isset($params['ttl']) && $params['ttl']) {
				$ttl_str = ltrim($params['ttl']);
				$ttl = 0;
				while ($ttl_str) {
					$factors = array(
						'h' => 3600,
						'm' => 60,
						's' => 1
					);
					$c = self::isPartialIntStr($ttl_str);
					if (!$c) {
						$ttl = 0;
						break;
					}
					$int = substr($ttl_str, 0, $c);
					$number = intval($int);
					$ttl_str = substr($ttl_str, $c);
					if ($ttl_str) {
						$factor = substr($ttl_str, 0, 1);
						$ttl_str = substr($ttl_str, 1);
						if (isset($factors[$factor]))
							$factor = $factors[$factor];
						else {
							$ttl = 0;
							break;
						}
					} else
						$factor = 1;
					$ttl += $number * $factor;
					$ttl_str = ltrim($ttl_str);
				}
				if ($ttl <= 0) {
					$ttl = 0;
					if (SERIA_DEBUG)
						die('Esi error: ttl format error: '.$params['ttl']);
				}
			} else
				$ttl = false;

			OR_EsiHtmlTokenCompiler::parseParams($params);

			$this->nodes[] = array('include', array('ttl' => $ttl, 'params' => $params));
		}

		

		protected static function parseParams(&$params) {
			if ($params) {
				$variables = array(
					"HTTP_ACCEPT_LANGUAGE" => array(
						'translate' => '
							return rawurlencode($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
						',
						'translateSub' => '
							$list = explode(",", $_SERVER["HTTP_ACCEPT_LANGUAGE");
							$list = array_flip($list);
							return isset($list[$sub]) ? "true" : "false");
						',
					),
					"HTTP_COOKIE" => array(
						'translate' => '
							return rawurlencode($_COOKIE);
						',
						'translateSub' => '
							return rawurlencode($_COOKIE[$sub]);
						',
					),
					"SERIA_SESSION" => array(
						'translate' => '
							return false;
						',
						'translateSub' => '
							return rawurlencode($_SESSION[$sub]);
						',

					),
					'SERIA_SESSION_ID' => array(
						'translate' => '
							return rawurlencode(session_id());
						',
						'translateSub' => '
							return false;
						',
					),
                                        'SERVER_NAME' => array(
                                                'translate' => '
                                                        return rawurlencode($_SERVER["SERVER_NAME"]);
                                                ',
                                                'translateSub' => '
                                                        return false;
                                                ',
                                        ),
                                        'REQUEST_URI' => array(
                                                'translate' => '
                                                        return rawurlencode($_SERVER["REQUEST_URI"]);
                                                ',
                                                'translateSub' => '
                                                        return false;
                                                ',
                                        ),
					"SERIA_USER" => array(
						'translate' => '
							return false;
						',
						'translateSub' => '
							$u = SERIA_Base::user();
							if ($u) {
								$a = array(
									"firstName" => $u->get("firstName"),
									"lastName" => $u->get("lastName"),
									"displayName" => $u->get("displayName"),
									"email" => $u->get("email"),
								);
								return rawurlencode($a[$sub]);
							} else {
								return false;
							}
						',
					),
					"HTTP_HOST" => array(
						'translate' => '
							return rawurlencode($_SERVER["HTTP_HOST"]);
						',
						'translateSub' => '
							return false;
						',
					),
					"HTTP_REFERER" => array(
						'translate' => '
							return rawurlencode($_SERVER["HTTP_REFERER"]);
						',
						'translateSub' => '
							return false;
						',
					),
                                        "SERIA_FULL_REQUEST_URL" => array(
                                                'translate' => '
                                                        return rawurlencode("http".($_SERVER["HTTPS"] ? "s" : "")."://".$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"]);
                                                ',
                                                'translateSub' => '
                                                        return false;
                                                ',
                                        ),
					"QUERY_STRING" => array(
						'translate' => '
							return rawurlencode($_SERVER["QUERY_STRING"]);
						',
						'translateSub' => '
							parse_str($_SERVER["QUERY_STRING"], $a);
							return rawurlencode($a[$sub]);
						',
					),
					"SERIA_ROAM_AUTH_PARAM" => array(
						'translate' => '
							return RoamAuth::getRoamAuthParam();
						',
						'translateSub' => '
							return false;
						',
					),
						
				);

				foreach ($params as $key => $value) {
					$continue = true;
					$pEnd = 0;
					while ($continue) {
						$pStart = strpos($value, '$(');
//						$pStart = strpos($value, '$(', $pEnd);
//echo "---------".$pStart." ----------------- ".$pEnd." --------- ".$value."<br>";
						if ($pStart !== false) {
							$p = $pStart + 2;
							$p2 = strpos($value, '{', $p);
							$p3 = strpos($value, ')', $p);
	
							if (($p2 !== false && $p3 !== false && $p3 < $p2) || $p2 === false) {
								$sub = false;
								$p2 = $p3;
							} else {
								$sub = true;
							}
	
							if ($p2 !== false) {
								$varName = substr($value, $p, ($p2 - $p));
								if ($variables[$varName]) {
									if ($sub) {
										$p3 = strpos($value, '}', $p2);
										if ($p3 !== false) {
											$subVariable = substr($value, ($p2 + 1), ($p3 - $p2 - 1));
											$pEnd = strpos($value, ')', $p3);
											if ($pEnd === false) throw new SERIA_Exception("Cant parse ESI variable");
										} else {
											throw new SERIA_Exception("Cant parse ESI variable");
										}
										$var = eval('$sub="'.$subVariable.'"; '.$variables[$varName]["translateSub"]);
									} else {
										$pEnd = $p3;
										$var = eval($variables[$varName]["translate"]);
									}
	
									$translate = $var;
									$translate = substr($value, 0, $pStart).$translate.substr($value, $pEnd + 1);
								} else {
									throw new SERIA_Exception("No support for ESI variable '".$varName."'");
								}
								$params[$key] = $translate;
								$value = $translate;
							} else {
								throw new SERIA_Exception("Cant parse ESI variable");
							}
						} else {
							$continue = false;
						}
					}
				}
			}

			return $params;
		 }

		protected function compileLoopEndHandler()
		{
			$cache = new SERIA_Cache('OR_EsiHtmlTokenCompiler');
			$browsers = new SERIA_WebBrowsers();
			$browsers->setTimeout(5);
			$browserCount = 0;
			foreach ($this->nodes as &$node) {
				if ($node[0] == 'include') {
					$params = $node[1]['params'];
					$cacheKey = 'ESI-include-data->'.$params['src'];
					if (($data = $cache->get($cacheKey))) {
						$ttl = $data['ttl'];
						$age = time() - $data['ts'];
						$node = array('text', '<!-- ESI from cache (ttl='.$ttl.', age='.$age.') : -->'.JEP_EsiIncludedHtmlTokenCompiler::recursiveCompile($data['content']));
					} else {
						$browser = new SERIA_WebBrowser();
						$browser->navigateTo($params['src']);
						$browsers->addWebBrowser($browser);
						unset($browser);
						$node[1]['browser'] = $browserCount;
						$browserCount++;
					}
				}
			}
			unset($node);
			if ($browserCount) {
				$datas = $browsers->fetchAll(false);
				$c = 0;
				foreach ($datas as $data) {
					while ($this->nodes) {
						$node = array_shift($this->nodes);
						if ($node[0] == 'text')
							$this->addOutput($node[1]);
						else if ($node[0] == 'include') {
							$ttl = $node[1]['ttl'];
							$params = $node[1]['params'];
							if ($node[1]['browser'] != $c)
								throw new SERIA_Exception('Broser table desync! ('.$node[1]['browser'].' != '.$c.')');
							break;
						} else
							throw new SERIA_Exception('Content chunk type not known: '.$node[0]);
					}
					$cacheKey = 'ESI-include-data->'.$params['src'];
					if ($data["data"]) {
						/*
						 * Get the cache headers..
						 */
						$debug = array();
						$webbrowser = $data['webbrowser'];
						$debug[] = 'Cache headers for: '.$params['src'];
						if ($ttl === false) {
							if (isset($webbrowser->responseHeaders['Date']))
								$debug[] = 'Header HTTP/1.0: Date: '.$webbrowser->responseHeaders['Date'];
							if (isset($webbrowser->responseHeaders['Expires']))
								$debug[] = 'Header HTTP/1.0: Expires: '.$webbrowser->responseHeaders['Expires'];
							if (isset($webbrowser->responseHeaders['Cache-Control']))
								$debug[] = 'Header HTTP/1.1: Cache-Control: '.$webbrowser->responseHeaders['Cache-Control'];
							if (isset($webbrowser->responseHeaders['Date']) && $webbrowser->responseHeaders['Date'] &&
							    isset($webbrowser->responseHeaders['Expires']) && $webbrowser->responseHeaders['Expires']) {
								$date = new DateTime($webbrowser->responseHeaders['Date']);
								$expires = new DateTime($webbrowser->responseHeaders['Expires']);
								$date = $date->getTimestamp();
								$expires = $expires->getTimestamp();
								$now = time();
								if ($expires >= $date) {
									$ttl = $expires - $now;
									if ($ttl < 0)
										$ttl = 0;
									$debug[] = 'Esi:include: HTTP/1.0 specifies public ttl='.$ttl;
								} else {
									$ttl = false;
									$debug[] = 'Esi:include: HTTP/1.0 specifies nocache';
								}
							}
							if (isset($webbrowser->responseHeaders['Cache-Control']) && $webbrowser->responseHeaders['Cache-Control']) {
								$cacheControl = new SERIA_CacheControl($webbrowser->responseHeaders['Cache-Control']);
								if (!$cacheControl->noCache() && !$cacheControl->noStore()) {
									if ($cacheControl->getToken('public') !== null) {
										$ttl = $cacheControl->getPublicMaxAge();
										$debug[] = 'Esi:include: HTTP/1.1 specifies public ttl='.($ttl !== null ? $ttl : 'unlimited');
										if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
											SERIA_ProxyServer::publicCache($ttl);
									} else if ($cacheControl->getToken('private') !== null) {
										$ttl = $cacheControl->getPrivateMaxAge();
										$debug[] = 'Esi:include: HTTP/1.1 specifies private ttl='.($ttl !== null ? $ttl : 'unlimited');
										if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
											SERIA_ProxyServer::privateCache($ttl);
									} else {
										$ttl = false;
										$debug[] = 'Esi:include: HTTP/1.1 specifies nocache (no caching directives)';
										if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
											SERIA_ProxyServer::noCache();
									}
								} else {
									$ttl = false;
									$debug[] = 'Esi:include: HTTP/1.1 specifies nocache';
									if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
										SERIA_ProxyServer::noCache();
								}
							} else if ($ttl !== false) {
								if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
									SERIA_ProxyServer::publicCache($ttl);
							} else {
								if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
									SERIA_ProxyServer::noCache();
							}
							$this->addOutput("<!-- Esi:include debug\n".implode("\n", $debug)."\n-->\n");
						} else {
							/*
							 * ttl-attribute on the esi:include overrides.
							 */
							if (!defined('ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS') || !ESIFRONTEND_DO_NOT_PASS_CACHE_HEADERS)
								SERIA_ProxyServer::publicCache($ttl);
						}
					} else {
						$data["data"] = "Could not fetch data";
						$ttl = 60;
					}
					if (!sizeof($_POST) && $ttl) {
						$cacheData = array(
							'ts' => time(),
							'ttl' => $ttl,
							'content' => $data['data']
						);
						$cache->set($cacheKey, $cacheData, $ttl);
					}
					$data["data"] = JEP_EsiIncludedHtmlTokenCompiler::recursiveCompile($data["data"]);
					$this->addOutput('<!-- ESI content fetched ttl='.$ttl.' -->'.$data['data']);
					$c++;
				}
			}
			foreach ($this->nodes as $node) {
				if ($node[0] != 'text')
					throw new SERIA_Exception('Content chunk type not known: '.$node[0]);
				$this->addOutput($node[1]);
			}
		}
	}


?>
