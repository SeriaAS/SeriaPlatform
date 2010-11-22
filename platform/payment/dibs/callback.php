<?php
	require('../../../main.php');
	SERIA_Template::disable();
	
	$key = $_POST['key'];
	$authkey = $_POST['authkey'];
	$transactionId = $_POST['transact'];
	
	$payment = SERIA_Payment_Dibses::find_first_by_key($key);
	if (!$payment) {
		throw new SERIA_Exception('Payment by key ' . $key . ' not found');
	}
	
	if ($authkey == SERIA_Payment_Dibs::calculateCallbackMd5Sum($transactionId, $payment->amount + $payment->shippingcost, SERIA_Payment_Dibs::convertToCurrencyCode($payment->currency))) {
		$payment->transactionid = $transactionId;
		$payment->status = SERIA_PAYMENT_SUCCESS;
		$payment->save();
		echo 'Ok';
	} else {
		$payment->status = SERIA_PAYMENT_FAILED;
		$payment->save();
		
		throw new SERIA_Exception('Payment ' . $payment->id . ' MD5 (' . $authkey . ') check failed');
	}
?>