<?php
	class SERIA_FlashPlayer implements SERIA_RPCServer {
		public static function rpc_getFlashPlayerData($objectKey, $stage=false) {
			return SERIA_VideoPlayer::rpc_getVideoPlayerData($objectKey, $stage);
		}
	}
?>
