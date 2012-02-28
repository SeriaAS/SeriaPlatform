<?php
	class OR_HtmlTokenCompiler {

		var $xmlNameSpace = "";

		protected $output = '';
		protected $compiled = false;

		protected function addOutput($output)
		{
			$this->output .= $output;
		}
		/**
		 *
		 * Hook into this by override to handle a text node.
		 * @param unknown_type $text
		 */
		protected function textNode($text)
		{
			$this->addOutput($text);
		}
		/**
		 *
		 * Hook into this by override to handle the EOF.
		 */
		protected function compileLoopEndHandler()
		{
		}
		public function __toString()
		{
			if (!$this->compiled)
				$this->compile();
			return $this->output;
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

		function compile()
		{
			$html = $this->html;
			$ns = $this->getXmlNs();
			$nsLength = strlen($ns) + 2;

			if (!$ns)
				throw new SERIA_Exception("No support for parsing without a namespace");

			$p = 0;
			$continue = true;
			$compiled = false;
			$debug = false;

			$parsed = array();
			while ($html) {
				$template = "";

				$ps = strpos($html, '<'.$ns.':');
				$pe = strpos($html, '</'.$ns.':');

				if ($ps === 0)
					$step = "startOfTag";
				else if ($pe === 0)
					$step = "endOfTag";
				else {
					$step = "text";
					$len = $ps;
					if ($len === false || ($pe !== false && $len > $pe))
						$len = $pe;
					if ($len === false)
						$len = strlen($html);
				}

				if ($step == 'text') {
					$text = substr($html, 0, $len);
					$html = substr($html, $len);
					$parsed[] = array('text', $text);
				} else if ($step == "startOfTag") {
					$startPos = 0;
					$p = $nsLength;
					$p2 = strpos($html, " ", $nsLength);
					$p3 = strpos($html, ">", $nsLength);
					$hasAttributes = ($p2 < $p3);

					if (!$hasAttributes) $p2 = $p3;

					$command = strtolower(substr($html, $nsLength, $p2 - $nsLength));
					if ($debug)
						echo "Start of tag: ".$command."<br>";

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
						$p2 = OR_HtmlTokenCompiler::endOfTag($html, $nsLength);
						$closedTag = (substr($html, $p2 - 1, 2) == "/>");
						$endPos = $p2;
						$params = array();
					}
					$parsed[] = array('startTag', array(
						'command' => $command,
						'params' => $params,
						'closed' => $closedTag
					));
					$html = substr($html, $endPos + 1);
				} else if ($step == "endOfTag") {
					/*
					 * Actually does nothing except handle it:
					 */
					$startPos = $pe;
					$p = $pe + 1;
					$endPos = OR_HtmlTokenCompiler::endOfTag($html, $p);
					$html = substr($html, $endPos);
				}
			}
			foreach ($parsed as $p) {
				if ($p[0] == 'text')
					$this->textNode($p[1]);
				else if ($p[0] == 'startTag') {
					call_user_func(array($this, $p[1]['command'].'Tag'), $p[1]['params']);
				} else
					throw new SERIA_Exception('Not implemented: '.$p[0]);
			}
			$this->compileLoopEndHandler();
			$this->compiled = true;

/*			if ($compiled) {
				$code = '<'.'?php'."\n";
				$code .= 'ob_start();'."\n";
				$code .= '$obReplace = array();'."\n";

				if ($this->preCode) {
					foreach ($this->preCode as $preCode) {
						$code .= $preCode."\n";
					}
				}

				$code .= '?>';
				$html = $code.$html;

				$code = '<'.'?php'."\n";
				$code .= '$contents = ob_get_contents();'."\n";
				$code .= 'ob_end_clean();'."\n";
				if ($this->postCode) {
					foreach ($this->postCode as $postCode) {
						$code .= $postCode."\n";
					}
				}	

				$code .= 'echo str_replace(array_keys($obReplace), array_values($obReplace), $contents);'."\n";
				$code .= '?>';
				$html .= $code;
			}
			return $html;
*/
		}

		function __construct($html, $xmlNameSpace = "")
		{
			$this->html = $html;
			$this->xmlNameSpace = $xmlNameSpace;
		}

		function getXmlNs() {
			return $this->xmlNameSpace;
		}
	}

?>
