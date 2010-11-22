<?php
	class OR_HtmlTokenCompiler {

		var $xmlNameSpace = "";
		private $preCode = array();
		private $postCode = array();

		function addPreCode($id, $code) {
			$this->preCode[$id] = $code;
		}

		function addPostCode($id, $code) {
			$this->postCode[$id] = $code;
		}

		static function explodeParams($s) {
			$len = strlen($s);

			$inQuote = false;
			$parsingStep = "key";
			$s1 = 0;
			$s2 = 0;
			$strName = "";
			for ($i = 0; $i < $len; $i++) {
				$char = $s[$i];
				switch ($parsingStep) {
					case "key":
						if ($char == "=") {
							$strName = trim(substr($s, $s1, $i - $s1));
							$parsingStep = "value";
							$firstValueChar = true;
							$quoteChar = false;
							$s1 = $i + 1;
						}
						break;
					case "value":
						if ($firstValueChar && ($char == "\"" || $char == "'")) {
							$quoteChar = $char;
							$firstValueChar = false;
						} else if ($firstValueChar && $char != " ") {
							$firstValueChar = false;
						} else if (!$firstValueChar) {
							if ($quoteChar && $char == $quoteChar || !$quoteChar && $char == " ") {
								$strValue = trim(substr($s, $s1, $i - $s1));
								if ($quoteChar) {
									$strValue = substr($s,($s1 + 1),($i - $s1 - 1));
								}
								$params[$strName] = str_replace(array("\"","'","<",">"), array("&quot;", "&apos;","&lt;","&gt;"), $strValue);
								$parsingStep = "key";
								$s1 = $i + 1;
							}
						}
						break;
				}
			}
			return $params;
		}

		static function endOfTag($html, $startPos=0) {
			$len = strlen($html);
			$inQuote = false;
			$quoteChar = false;

			for ($i = $startPos; $i < $len; $i++) {
				if (!$inQuote && $html[$i] == ">") {
					return $i;
				} else if (!$inQuote && ($html[$i] == "\"" || $html[$i] == "'")) {
					$quoteChar = $html[$i];
					$inQuote = true;
				} else if ($inQuote && $html[$i] == $quoteChar) {
					$inQuote = false;
				}
			}
			throw new SERIA_Exception("Cant find end of tag");
		}

		function compile($html) {
			return OR_HtmlTokenCompiler::compileHTML($this, $html);
		}

		function __construct($xmlNameSpace = "") {
			$this->xmlNameSpace = $xmlNameSpace;
		}

		static function compileHtml($c, $html) {
			$ns = $c->getXmlNs();
			$nsLength = strlen($ns) + 2;

			if (!$ns) throw new SERIA_Exception("No support for parsing without a namespace");

			$p = 0;
			$continue = true;
			$compiled = false;
$debug = false;

			while ($continue) {
	
				$template = "";

	                        $ps = strpos($html, '<'.$ns.':', $p);
				$pe = strpos($html, '</'.$ns.':', $p);

				if (($ps < $pe && $ps !== false) || ($pe === false && $ps !== false)) $step = "startOfTag";
				else if ($pe !== false) $step = "endOfTag";
				else $step = "noTag";

				if ($step == "startOfTag") {
					$compiled = true;
					$p = $ps;
					$startPos = $p;
					$p += $nsLength;
	                                $p2 = strpos($html, " ", $p);
					$p3 = strpos($html, ">", $p);
					$hasAttributes = ($p2 < $p3);

					if (!$hasAttributes) $p2 = $p3;

					$template .= '<'."?php\n";
                                        $command = strtolower(substr($html, $p, $p2 - $p));
if ($debug) echo "Start of tag: ".$command."<br>";

					if ($hasAttributes) {
						$p = $p2 + 1;
						$p2 = OR_HtmlTokenCompiler::endOfTag($html, $p);
						$endPos = $p2;
						if (substr($html, $p2 - 1, 2) == "/>") {
							$closedTag = true;
							$s = substr($html, $p, $p2 - $p - 1);
						} else {
							$closedTag = false;
							$s = substr($html, $p, $p2 - $p);
						}
						$params = OR_HtmlTokenCompiler::explodeParams($s);

					} else {
						$p2 = OR_HtmlTokenCompiler::endOfTag($html, $p);
						$endPos = $p2;
						$params = array();
					}

					$template .= call_user_func(array($c, $command."Tag"), $params);

					$template .= '?>';

				} else if ($step == "endOfTag") {
					$startPos = $pe;
					$p = $pe + 1;
					$endPos = OR_HtmlTokenCompiler::endOfTag($html, $p);
				} else {
					$continue = false;
				}

				if ($continue) {
					$html = substr($html, 0, $startPos).$template.substr($html, $endPos + 1);
					$template = "";
					$p = 0;
				}
			}

			if ($compiled) {
				$code = '<'.'?php'."\n";
				$code .= 'ob_start();'."\n";
				$code .= '$obReplace = array();'."\n";

				if ($c->preCode) {
					foreach ($c->preCode as $preCode) {
						$code .= $preCode."\n";
					}
				}

				$code .= '?>';
				$html = $code.$html;

				$code = '<'.'?php'."\n";
				$code .= '$contents = ob_get_contents();'."\n";
				$code .= 'ob_end_clean();'."\n";
				if ($c->postCode) {
					foreach ($c->postCode as $postCode) {
						$code .= $postCode."\n";
					}
				}	

				$code .= 'echo str_replace(array_keys($obReplace), array_values($obReplace), $contents);'."\n";
				$code .= '?>';
				$html .= $code;


			}


			return $html;
		}

		function getXmlNs() {
			return $this->xmlNameSpace;
		}
	}

?>
