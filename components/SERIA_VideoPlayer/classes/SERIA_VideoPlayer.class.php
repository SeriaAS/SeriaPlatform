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

		public function getIFrameUrl($width="100%",$height="100%") {
// DEMO STATISTICS
/*
$sc = new SERIA_Counter('joakim');
$sc->add(array('totalviews', 'totalviews_2011_03_11', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_01', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_02', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_03', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_04', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_05', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_06', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_08', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_07', 'totalviews_2011_03'),rand(0,1000));
$sc->add(array('totalviews', 'totalviews_2011_03_10', 'totalviews_2011_03'),rand(0,1000));
*/
			return SERIA_Meta::manifestUrl('videoplayer','iframe', array('objectKey' => SERIA_NamedObjects::getPublicId($this->_object)));
		}

		public function output($width="100%",$height="100%") {
			if(trim($width, "%")==$width) $width .= 'px';
			if(trim($height, "%")==$height) $height .= 'px';
			return "<iframe src='".$this->getIFrameUrl($width,$height)."' style='width:".$width.";height:".$height.";border:none;margin:0;padding:0;' frameborder='0'>Your browser does not support this type of video. Read more <a href='http://www.seriatv.com/help/iframe-embedding-video'>about web based video content management with Flash and HTML 5</a>.</iframe>";
//			return "<iframe src='".SERIA_HTTP_ROOT."/seria/components/SERIA_VideoPlayer/assets/player.php?objectKey=".rawurlencode(SERIA_NamedObjects::getPublicId($this->_object))."' frameborder='0' style='border: none; width: $width"."px; height: $height"."px;'></iframe>";

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

			if(!isset($this->_modules['controlBar']))
				$flashvars['controlBar'] = urlencode(SERIA_HTTP_ROOT.'/seria/components/SERIA_VideoPlayer/bin/SeriaControlbar.swf');


			return "<object classid='clsid:D27CDB6E-AE6D-11cf-96B8-444553540000' width='$width' height='$height'>
<param name='movie' value='".SERIA_HTTP_ROOT."/seria/components/SERIA_VideoPlayer/bin/SeriaPlayer.swf'></param>
<param name='allowFullscreen' value='true'></param>
<param name='wmode' value='opaque'></param>
<param name='allowscriptaccess' value='always'></param> 
<param name='flashvars' value='".$this->_flashVarsToString($flashvars)."'></param>
<!--[if !IE]>-->
<object type='application/x-shockwave-flash' data='".SERIA_HTTP_ROOT."/seria/components/SERIA_VideoPlayer/bin/SeriaPlayer.swf' width='$width' height='$height'>
<param name='flashvars' value='".$this->_flashVarsToString($flashvars)."'></param>
<param name='allowscriptaccess' value='always'></param> 
<param name='wmode' value='opaque'></param>
<param name='allowFullscreen' value='true'></param>
<!--<![endif]-->
her
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
		*	Count a simple event such as 'play' in a manner suitable for breakdown into time ranges. Will count
		*	totals as well as one per objectKey. The process is asynchonous, meaning that events are logged to a
		*	file then committed to the database at certain intervals.
		*/
		public static function rpc_countSimpleEvent($objectKey, $name) {
			$names = explode(",", $name);
			foreach($names as $name)
			{
				$fp = fopen(SERIA_LOG_ROOT.'/SERIA_VideoPlayer/countSimpleEvent.'.$name.'.'.$objectKey.'.log', 'ab');
				fwrite($fp, date('Y:m:d:H:N')."\n");
				fclose($fp);
				$fp = fopen(SERIA_LOG_ROOT.'/SERIA_VideoPlayer/countsimpleEvent.log', 'ab');
				fwrite($fp, date('Y:m:d H:i:s')."\t".$_SERVER['REMOTE_ADDR']."\t".$_SERVER['USER_AGENT']."\t".$name."\t".$objectKey."\n");
				fclose($fp);
			}
		}

		public static function commitSimpleEvents() {
			if(!$this->counter) $this->counter = new SERIA_Counter('SERIA_VideoPlayer');
			list($y,$m,$d,$h,$wd) = explode(':', date('Y:m:d:H:N'));
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
		}

		/**
		* $metaObjectGUID is for example: 'SERIA_Video:5353'
		*/
		public static function rpc_getVideoPlayerData($objectKey, $stage=false)
		{
			$o = SERIA_NamedObjects::getInstanceByPublicId($objectKey);

			if(!($o instanceof SERIA_IVideoData)) {
				throw new SERIA_Exception($objectName.' is not an instance of SERIA_IVideoData');
			}
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
