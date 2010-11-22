<?php
	require('../../../main.php');
	SERIA_Template::disable();
	
	$payment = SERIA_Payment_Dibses::find($_GET['id']);
	if (!$payment) {
		die('Payment not found');
	}
	
	$payment->key = $payment->id . '_' . sha1(mt_rand(0,1000000) . $_SERVER['REMOTE_ADDR'] . time());
	$payment->save();
	
	$params = array();
	
	// Default options for all orders
	$params['ip'] = $_SERVER['REMOTE_ADDR'];
	$params['uniqueoid'] = true;
	
	// Authentication data for server
	$params['merchant'] = SERIA_PAYMENT_DIBS_MERCHANT_ID;
	
	// Set to test mode if not configured for production mode
	if (!defined('SERIA_PAYMENT_DIBS_PRODUCTION') || !SERIA_PAYMENT_DIBS_PRODUCTION) {
		$params['test'] = 'test';
	}
	
	
	// Set language code
	$language = strtolower($payment->language);
	
	switch ($language) {
		case 'nb':
		case 'nn':
			$language = 'no';
			break;
	}
	
	$params['lang'] = $language;
	
	$params['orderid'] = $payment->ordernumber;
	$params['accepturl'] = SERIA_HTTP_ROOT . '/seria/platform/payment/dibs/accept.php?payment_key=' . $payment->key;
	$params['cancelurl'] = SERIA_HTTP_ROOT . '/seria/platform/payment/dibs/cancel.php?payment_key=' . $payment->key;
	$params['callbackurl'] = SERIA_HTTP_ROOT . '/seria/platform/payment/dibs/callback.php?key=' . $payment->key;
	
	// Set currency code
	$params['currency'] = SERIA_Payment_Dibs::convertToCurrencyCode($payment->currency);
	
	// Payment data
	$params['amount'] = $payment->amount + $payment->shippingcost;
	
	$params['md5key'] = SERIA_Payment_Dibs::calculateAuthCgiMd5Key($params);
?>
<html>
	<head>
		<title>Payment</title>
		<script type="text/javascript">
			function toPayment() {
					document.getElementsByName('payment')[0].submit();
			}
		</script>
	</head>
	<body onload="toPayment()">
		<form action="https://payment.architrade.com/paymentweb/start.action" method="post" name="payment">
			<?php foreach ($params as $key => $value) { ?>
				<input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($value); ?>">
			<?php }?>
			<noscript>
				<input type="submit" value="<?php echo _t('Continue to payment'); ?>">
			</noscript>
		</form>
	</body>
</html>