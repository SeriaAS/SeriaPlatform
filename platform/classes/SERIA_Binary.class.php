<?php
	class SERIA_Binary {
		public static function decodeUint32($data) {
			if (strlen($data) != 4) {
				throw new Exception('decodeUint32 required 4 bytes of data');
			}
			
			$data2 = unpack('Nint', $data);
			return $data2['int'];
		}
		
		public static function decodeUint24($data) {
			if (strlen($data) != 3) {
				throw new Exception('decodeUint24 required 3 bytes of data');
			}
			
			$data = chr(0) . $data;
			return self::decodeUint32($data);
		}
	}
?>