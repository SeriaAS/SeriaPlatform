<?php
	class SERIA_HtmlFlash {
		public static function notice($message) {
			$_SESSION['flashNotice'] = $message;
		}
		
		public static function error($message) {
			$_SESSION['flashError'] = $message;
		}
		
		public static function getHtml() {
			SERIA_Base::preventCaching();
			$notice =& $_SESSION['flashNotice'];
			$error =& $_SESSION['flashError'];
			
			if(!$notice && !$error) return "";

			$html  = '<div class="flashMessages">';
			if ($notice) {
				$html .= '<p class="flashNotice">' . $notice . '</p>';
			}
			if ($error) {
				$html .= '<p class="flashError">' . $error . '</p>';
			}
			
			$html .= '</div>';
			
			$notice = '';
			$error = '';
			
			return $html;
		}
		
		public static function show() {
			SERIA_Base::preventCaching();
			echo self::getHtml();
		}

		public static function getMessages() {
			SERIA_ProxyServer::noCache();

			$notice = $_SESSION['flashNotice'];
			$error = $_SESSION['flashError'];

			unset($_SESSION["flashNotice"]);
			unset($_SESSION["flashError"]);

			$result = array();
			if($notice) $result["flashNotice"] = $notice;
			if($error) $result["flashError"] = $error;

			return $result;
		}
	}
?>
