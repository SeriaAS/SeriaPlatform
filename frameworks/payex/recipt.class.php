<?php
class recipt
{

        public $accountNumber = SERIA_PAYMENT_PAYEX_ACCOUNT_NUMBER; // NB: Merchant account number REMEMBER TO SET MERCHANT ENCRYPTION KEY IN FUNCTIONS.PHP

        function Complete($orderRef=false)
        {
                $order = new order();
                $functions = new functions();
                if($orderRef===false)
                        $orderRef = stripcslashes($_GET['orderRef']);

                $params = array
                (
                        'accountNumber' => $this->accountNumber,
                        'orderRef' => $orderRef
                );

                $completeResponse = $order->Complete($params);
                $result = $functions->complete($completeResponse);

		print_r($result);
		/*
		Transaction statuses (defined in payex_defines.php):
		0=Sale, 1=Initialize, 2=Credit, 3=Authorize, 4=Cancel, 5=Failure, 6=Capture
		*/
                return $result["transactionStatus"];

        }

}
