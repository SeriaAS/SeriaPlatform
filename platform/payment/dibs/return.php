<?php
	if (!$action) {
		die();
	}

	$payment_key = $_POST['payment_key'];
	if ($payment_key) {
		require('../../../main.php');
		SERIA_Template::disable();
		$payment = SERIA_Payment_Dibses::find_first_by_key($payment_key);
		if ($payment) {
			$url = '';
			switch ($action) {
				case 'success':
					if ($payment->status == SERIA_PAYMENT_SUCCESS) {
						$url = $payment->successurl;
					} else {
						$url = $payment->failureurl;
					}
					break;
				case 'cancel':
					$url = $payment->failureurl;
					break;
			}
			header('Location: ' . $url);
			die();
		}
	}

?>