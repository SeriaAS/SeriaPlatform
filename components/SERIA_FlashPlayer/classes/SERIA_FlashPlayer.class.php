<?php

	class SERIA_FlashPlayer implements SERIA_RPCServer {

		private $_object;

		public function __construct($object) {
			$this->_object = $object;
		}

		public function output() {
			//SERIA_ScriptLoader::loadScript('js/flowplayer-3.2.4.min.js');
			return '<object id="SERIA_FlashPlayer" width="100%" height="100%" data="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_FlashPlayer/bin/SeriaPlayer.swf" 
					type="application/x-shockwave-flash">

					<!-- load configuration from config.jsp -->
					<param name="flashvars" value=\''.(($_GET["debugMode"] && SERIA_Base::isLoggedIn()) ? 'debugMode=true' : '').'&httpRoot='.urlencode(SERIA_HTTP_ROOT).'&amp;objectKey='.SERIA_NamedObjects::getPublicKey($this->_object).'\' />
					<param name="movie" value="'.SERIA_HTTP_ROOT.'/seria/components/SERIA_FlashPlayer/bin/SeriaPlayer.swf" />
					<param name="allowfullscreen" value="true" />
				</object>';
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
	}
