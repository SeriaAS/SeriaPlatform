<?php
	class OR_EsiHtmlTokenCompiler extends OR_HtmlTokenCompiler {

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

		function includeTag($params) {
			static $counter = 0;

			$cns = 'OR_EsiHtmlTokenCompiler';
			$cache = new SERIA_Cache($cns);

			if (!$params["src"]) throw new SERIA_Exception("src attribute is required in ESI include tag");
			if (strpos($params["src"], "http://") !== 0 && strpos($params["src"], "https://") !== 0) throw new SERIA_Exception("Security alert in ESI include tag");
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
					$ttl = 300;
					if (SERIA_DEBUG)
						die('Esi error: ttl format error: '.$params['ttl']);
				}
			} else
				$ttl = 300;

			OR_EsiHtmlTokenCompiler::parseParams($params);

			$cacheKey = 'ESI-include->'.$params['src'];
			if (($code = $cache->get($cacheKey))) {
				$code = JEP_EsiIncludedHtmlTokenCompiler::recursiveCompile($code);
				return 'echo '.var_export($code, true).";\n";
			}

			$code = '$browsers = new SERIA_WebBrowsers();'."\n";
			$code .= '$browsers->setTimeout(5);'."\n";
			$code .= '$esiDataCache = new SERIA_Cache('.var_export($cns, true).');';
			$this->addPreCode("include", $code);

			$code = '$datas = $browsers->fetchAll(true);'."\n";
			$code .= '$c = 0;'."\n";
			$code .= 'foreach ($datas as $data) {'."\n";
			$code .= '	if (!$data["data"])'."\n";
			$code .= '		$data["data"] = "Could not fetch data";'."\n";
			$code .= '	if (!sizeof($_POST)) {';
			$code .= '		$esiDataCache->set('.var_export($cacheKey, true).', $data["data"], '.var_export($ttl, true).');'."\n";
			$code .= '	}';
			$code .= '	$data["data"] = JEP_EsiIncludedHtmlTokenCompiler::recursiveCompile($data["data"]);';
			$code .= '	$obReplace["%WEB_BROWSER_".$c."%"] = $data["data"];'."\n";


			$code .= '	$c++;'."\n";
			$code .= '}'."\n";
			$this->addPostCode("include", $code);

			$code = '$wb'.$counter.' = new SERIA_WebBrowser();'."\n";
			$code .= '$wb'.$counter.'->navigateTo("'.$params["src"].'");'."\n";
			$code .= '$browsers->addWebBrowser($wb'.$counter.');'."\n";
			$code .= 'echo "%WEB_BROWSER_'.$counter.'%";'."\n";

//			$code .= 'echo "<div style=\"border: 1px wave red;\">'.$params["src"].'</div>";'."\n";

			$counter++;

			return $code;
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

	}


?>
