<?php

	class SERIA_FlashPlayer implements SERIA_RPCServer {

		private $_object;
		private $_modules;

		public function __construct($object) {
			$this->_object = $object;
		}

		public function addModule($moduleName, $moduleUrl) {
			$this->_modules[$moduleName] = $moduleUrl;
		}

		public function output() {
			//SERIA_ScriptLoader::loadScript('js/flowplayer-3.2.4.min.js');
			$flashvars = array(
				'debugMode' => ((SERIA_Base::isLoggedIn() && $_GET["debugMode"]) ? 'true' : ''),
				'httpRoot' => urlencode(SERIA_HTTP_ROOT),
				'objectKey' => SERIA_NamedObjects::getPublicKey($this->_object),
				'tracker_code' => GoogleAnalyticsComponent::getGoogleAnalyticsId(),
			);

			foreach($this->_modules as $moduleName => $moduleURL) {
				$flashvars[$moduleName] = urlencode($moduleURL);
			}

			return "<object width='100%' height='100%' id='SERIA_FlashPlayer'>
					<param name='allowFullscreen' value='true' />
					<param value='opaque'/>
					<param name='movie' value='".SERIA_HTTP_ROOT."/seria/components/SERIA_FlashPlayer/bin/SeriaPlayer.swf' />
					<param name='allowscriptaccess' value='always' />
					<param name='quality' value='high' />
					<param name='flashvars' value='".$this->_flashVarsToString($flashvars)."' />
					<embed src='".SERIA_HTTP_ROOT."/seria/components/SERIA_FlashPlayer/bin/SeriaPlayer.swf?".$this->_flashVarsToString($flashvars)."'
					        quality='high'
					        width='100%'
					        height='100%'
					        name='SERIA_FlashPlayer'
					        align='middle'
					        play='true'
					        loop='false'
					        allowFullscreen='true'
					        allowScriptAccess='always'
					        type='application/x-shockwave-flash'
					        pluginspage='http://www.adobe.com/go/getflashplayer'>
				        </embed>
				</object>";
		}

		public function generateConfig()
		{
			return '';
		}

		/**
		* $metaObjectGUID is for example: 'SERIA_Video:5353'
		* MUST be SERIA_MetaObject:ID
		*
		*
		*/
		public static function rpc_getFlashPlayerData($objectKey)
		{
			$o = SERIA_NamedObjects::getInstanceByPublicKey($objectKey);

			if(!($o instanceof SERIA_IVideoData)) {
				throw new SERIA_Exception($objectName.' is not an instance of SERIA_IVideoData');
			}

			return $o->getVideoData();
		}

		protected function _flashVarsToString($flashvarsArray)
		{
			$flashvarString = '';

			foreach($flashvarsArray as $key => $val) {
				if(!empty($val) && ($val != '') && isset($val))
					$flashvarString .= $key.'='.$val.'&';
			}

			return $flashvarString;
		}
	}
