<?php
	class SERIA_Payment {
		/**
		 * Start a new transaction. This will return a payment object.
		 * @throws SERIA_Exception If requested payment provider does not exists, or is invalid.
		 * @return SERIA_PaymentProvider
		 */
		public static function startSession() {
			$providerName = SERIA_PAYMENT_PROVIDER;
			$className = 'SERIA_Payment_' . $providerName;
			if (!class_exists($className)) {
				throw new SERIA_Exception('Class for payment provider ' . $providerName . ' does not exist');
			}
			
			$found = false;
			foreach (class_implements($className) as $interface) {
				if ($interface == 'SERIA_PaymentProvider') {
					$found = true;
					break;
				}
			}
			if (!$found) {
				throw new SERIA_Exception('Class for payment provider ' . $providerName . ' does not implement SERIA_PaymentProvider');
			}
			
			return new $className();
		}
		public static function paymentProviderExists()
		{
			if(!SERIA_PAYMENT_PROVIDER) return false;
			$providerName = SERIA_PAYMENT_PROVIDER;
			$className = 'SERIA_Payment_' . $providerName;
			return class_exists($className);
		}
	}
?>
