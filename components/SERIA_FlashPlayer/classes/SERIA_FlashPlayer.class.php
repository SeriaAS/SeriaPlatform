<?php

	class SERIA_FlashPlayer implements SERIA_RPCServer {

		private $_object;
		private $_modules;

		public function __construct($object) {
			if(!($object instanceof SERIA_IVideoData)) {
				throw new SERIA_Exception($object.' is not an instance of SERIA_IVideoData');
			}
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
				'objectKey' => SERIA_NamedObjects::getPublicId($this->_object),
				'trackerCode' => GoogleAnalyticsComponent::getGoogleAnalyticsId(),
			);

			foreach($this->_modules as $moduleName => $moduleURL) {
				$flashvars[$moduleName] = urlencode($moduleURL);
			}

			return "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='100%' height='100%'>
<param name='movie' value='".SERIA_HTTP_ROOT."/seria/components/SERIA_FlashPlayer/bin/SeriaPlayer.swf'></param>
<param name='allowFullscreen' value='true'></param>
<param name='allowscriptaccess' value='always'></param> 
<param name='flashvars' value='".$this->_flashVarsToString($flashvars)."'></param>
<!--[if !IE]>-->
<object type='application/x-shockwave-flash' data='".SERIA_HTTP_ROOT."/seria/components/SERIA_FlashPlayer/bin/SeriaPlayer.swf' width='100%' height='100%'>
<param name='flashvars' value='".$this->_flashVarsToString($flashvars)."'></param>
<param name='allowscriptaccess' value='always'></param> 
<param name='allowFullscreen' value='true'></param>
<!--<![endif]-->
<!--[if !IE]>-->
</object>
<!--<![endif]-->
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
		public static function rpc_getFlashPlayerData($objectKey, $stage=false)
		{
			$o = SERIA_NamedObjects::getInstanceByPublicId($objectKey);

			if(!($o instanceof SERIA_IVideoData)) {
				throw new SERIA_Exception($objectName.' is not an instance of SERIA_IVideoData');
			}
			if($stage) {
				$arrayS = $o->getVideoData();
				$arrayS['site'] = $_SER;
				return $arrayS;
			} else {
				return $o->getVideoData();
			}
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
