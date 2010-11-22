<?php 
	require_once(dirname(__FILE__)."/../../main.php");

	SERIA_Base::addFramework("payex");

	//$payex = SERIA_Payment::startSession();
	$orderRef = $_GET["orderRef"];
	$successUrl = $_GET["success"];
	$failureUrl = $_GET["failure"];

	$pc = new recipt();
	
        $result = $pc->Complete($orderRef);
        if($result==3) // Success
        {
		$payex = SERIA_Payment_PayExs::find_first_by_transactionid($orderRef);
		$payex->setPaymentStatus('AUTH');
		$payex->save();
		header("Location: ".$successUrl);
        } else
        {
		$payex = SERIA_Payment_PayExs::find_first_by_transactionid($orderRef);
		$payex->setPaymentStatus('FAIL');
		$payex->save();
      		header("Location: ".$failureUrl);
        }

