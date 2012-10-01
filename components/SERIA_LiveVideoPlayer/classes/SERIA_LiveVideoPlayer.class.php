<?php

	class SERIA_LiveVideoPlayer implements SERIA_RPCServer {

		private $_object;
		private $_modules;

		public function __construct($object) {
			if(!($object instanceof SERIA_ILiveVideoData)) {
				throw new SERIA_Exception($object.' is not an instance of SERIA_ILiveVideoData');
			}
			$this->_object = $object;
		}

		public function addModule($moduleName, $moduleUrl) {
			$this->_modules[$moduleName] = $moduleUrl;
		}

		public function output($width="580", $height="360", $options = NULL) {
			//return '<div>joakim</div>';
			//return '<iframe src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/pages/flowplayer.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'" frameborder="0" scrolling="no">Your browser does not support frames</iframe>';
			if($options === NULL) {
				return '<script id="playerScript_'.$this->_object->get("id").'" src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/js/flowplayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.'"></script>';
			}
			$optionHtml = "";
			foreach($options as $key => $option)
				$optionHtml.= "&".$key."=".$option;
			return '<script id="playerScript_'.$this->_object->get("id").'"  src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/js/flowplayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.$optionHtml.'"></script>';
		}

		public function getScriptURL($width="580", $height="360", $options = NULL) {
/*
			return '<div>joakim</div>';
			if($options === NULL) {
				return '<iframe src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/pages/flowplayer.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&height='.$height.'&width='.$width.'" frameborder="0" scrolling="no">Your browser does not support frames</iframe>';
			}
			$optionHtml = "";
			foreach($options as $key => $option)
				$optionHtml.= "&".$key."=".$option;
			return '<iframe src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/pages/flowplayer.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.$optionHtml.'" frameborder="0" scrolling="no">Your browser does not support frames</iframe>';
			return SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/js/flowplayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.$optionHtml;
*/

			if($options === NULL) {
				return SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/js/flowplayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height;
			}
			$optionHtml = "";
			foreach($options as $key => $option)
				$optionHtml.= "&".$key."=".$option;
			return SERIA_HTTP_ROOT.'/seria/components/SERIA_LiveVideoPlayer/js/flowplayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.$optionHtml;

		}

		public static function rpc_getVideoPlayerData($objectKey, $stage=false)
		{
			if(SERIA_Base::hasSystemAccess()) SERIA_Base::viewMode('system');
			$o = SERIA_NamedObjects::getInstanceByPublicId($objectKey);

			if(!($o instanceof SERIA_IVideoData)) {
				throw new SERIA_Exception($objectName.' is not an instance of SERIA_IVideoData');
			}

			return $o->getVideoData();

			if($stage) {
				$arrayS = $o->getVideoData();
				//$arrayS['site'] = $_SER;
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


