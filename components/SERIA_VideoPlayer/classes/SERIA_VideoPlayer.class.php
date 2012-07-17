<?php

	class SERIA_VideoPlayer implements SERIA_RPCServer {

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

		public function getScriptableUrl($width="580", $height="360", $options = NULL) {
			if($options === NULL)
				return '<script src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_VideoPlayer/js/strobePlayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.'"></script>';
			$addOptions = "";
			foreach($options as $key => $val)
				$addOptions.= "&".$key."=".$val;
			return '<script src="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_VideoPlayer/js/strobePlayer.js.php?objectKey='.SERIA_NamedObjects::getPublicId($this->_object).'&width='.$width.'&height='.$height.''.$addOptions.'"></script>';
		}

		public function getIFrameUrl($width="100%",$height="100%", $options = NULL, $preventRandom = false) {

			$newPlayer = array('ebs.seriatv.com', 'webcast.seriatv.com', 'hegnar.seriatv.com', 'hoyre.seriatv.com', 'custom.seriatv.com', 'ebs.seriatv.com.dev.seria.net', 'tibe.seriatv.com');

			foreach($newPlayer as $domain)
			{
				if($_SERVER['HTTP_HOST'] == $domain)
				{
					if($options === NULL)
						return SERIA_Meta::manifestUrl('videoplayer','strobeframe', array('objectKey' => SERIA_NamedObjects::getPublicId($this->_object), '_r' => mt_rand(0,9999999)));
					return SERIA_Meta::manifestUrl('videoplayer','strobeframe', array_merge($options, array('objectKey' => SERIA_NamedObjects::getPublicId($this->_object), '_r' => mt_rand(0,9999999))));
				}
			}

if(is_a($this->_object, 'SERIA_LiveProgram') && $this->_object->get("id") == 76)
return SERIA_Meta::manifestUrl('videoplayer','iframe_webcast', array('objectKey' => SERIA_NamedObjects::getPublicId($this->_object), '_r' => mt_rand(0,9999999)));
			if($options === NULL)
				return SERIA_Meta::manifestUrl('videoplayer','iframe', array('objectKey' => SERIA_NamedObjects::getPublicId($this->_object), '_r' => ($preventRandom ? 1 : mt_rand(0,9999999))));
			return SERIA_Meta::manifestUrl('videoplayer','iframe', array_merge($options, array('objectKey' => SERIA_NamedObjects::getPublicId($this->_object), '_r' => ($preventRandom ? 1 : mt_rand(0,9999999)))));
		}
		/**
		* $options will include variables sent to the flash player or html5 video player
		*
		* ie $options = array(
		*	'autoplay' => true,
		*	'wmode' => 'opaque'
		*    );
		*
		*/
		public function output($width="100%",$height="100%",$options = NULL, $preventRandom = false, $client = NULL, $expires = NULL) {
			if(trim($width, "%")==$width) $width .= 'px';
			if(trim($height, "%")==$height) $height .= 'px';
			$url = new SERIA_Url($this->getIFrameUrl($width,$height,$options, $preventRandom, $client, $expires));
			if($expires!==NULL)
				$url->setParam('expires', $expires);
			if($client!==NULL) {
				$url->setParam('clientId', $client->get('client_id'));
				$url->sign($client->get('client_key'));
			}

			return "<iframe src='".$url."' style='width:".$width.";height:".$height.";border:none;margin:0;padding:0;' frameborder='0'>Your browser does not support this type of video. Read more <a href='http://www.seriatv.com/help/iframe-embedding-video'>about web based video content management with Flash and HTML 5</a>.</iframe>";
		}

		public function generateConfig()
		{
			return '';
		}

		/**
		*	Count a simple event such as 'play' in a manner suitable for breakdown into time ranges. Will count
		*	totals as well as one per objectKey. The process is asynchonous, meaning that events are logged to a
		*	file then committed to the database at certain intervals.
		*/
		public static function rpc_countSimpleEvent($objectKey, $name) {
			$names = explode(",", $name);
			$fpAll = fopen(SERIA_LOG_ROOT.'/SERIA_VideoPlayer/countsimpleEvent.log', 'ab');
			foreach($names as $name)
			{
				$fp = fopen(SERIA_LOG_ROOT.'/SERIA_VideoPlayer/countSimpleEvent.'.$name.'.'.$objectKey.'.log', 'ab');
				fwrite($fp, date('Y:m:d:H:N')."\n");
				fclose($fp);
				fwrite($fpAll, date('Y:m:d H:i:s')."\t".$_SERVER['REMOTE_ADDR']."\t".$_SERVER['USER_AGENT']."\t".$name."\t".$objectKey."\n");
			}
			fclose($fpAll);

			return true;
		}

		public static function commitSimpleEvents() {
			$counter = new SERIA_Counter('SERIA_VideoPlayer');
			$files = glob(SERIA_LOG_ROOT.'/SERIA_VideoPlayer/countSimpleEvent.*.*.log');
			foreach($files as $file)
			{
				list($type, $name, $objectKey) = explode(".", substr($file, strrpos($file, '/')+1));

				if(!file_exists($file.'.tmp'))
				{ // the file does not exist, so it is safe to take away the current statistics
					rename($file, $file.'.tmp');
				}

				$fp = fopen($file.'.tmp', 'r');
				while($line = trim(fgets($fp, 4096)))
				{
					list($y,$m,$d,$h,$wd) = explode(":", $line);
					$keys = array(
						"e=$name&y=$y",
						"e=$name&y=$y&m=$m",
						"e=$name&y=$y&m=$m&d=$d",
						"e=$name&y=$y&m=$m&wd=$wd",
						"e=$name&wd=$wd",
						"e=$name&y=$y&h=$h",
						"e=$name&y=$y&m=$m&h=$h",

						"e=$name&y=$y&ok=$objectKey",
						"e=$name&y=$y&m=$m&ok=$objectKey",
						"e=$name&y=$y&m=$m&d=$d&ok=$objectKey",
						"e=$name&y=$y&m=$m&wd=$wd&ok=$objectKey",
						"e=$name&wd=$wd&ok=$objectKey",
						"e=$name&y=$y&h=$h&ok=$objectKey",
						"e=$name&y=$y&m=$m&h=$h&ok=$objectKey",
					);
					$counter->add($keys);
				}
				fclose($fp);
				unlink($file.'.tmp');
			}
		}

		/**
		* $metaObjectGUID is for example: 'SERIA_Video:5353'
		*/
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


