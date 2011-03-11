<?php
	class SERIA_FlashPlayer implements SERIA_RPCServer {
		function __construct() { throw new SERIA_Exception('Use SERIA_VideoPlayer, not SERIA_FlashPlayer'); }

		public static function rpc_getFlashPlayerData($objectKey, $stage=false) {
			return SERIA_VideoPlayer::rpc_getVideoPlayerData($objectKey, $stage);
		}
	}
?>
