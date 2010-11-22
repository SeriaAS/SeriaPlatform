<?php
	function payment_maintain() {
		if (SERIA_Payment::paymentProviderExists()) {
			$paymentProvider = SERIA_Payment::startSession();
			return $paymentProvider->maintain();
		}
	}
?>