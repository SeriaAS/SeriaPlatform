<?php
	class SERIA_Payment_PayEx extends SERIA_ActiveRecord implements SERIA_PaymentProvider {

		/**
	 	 * This Payment structure requires the following configurations:
		 * PAYEX_SHOP_NAME
		 * PAYEX_SHOP_DESCRIPTION
		 */		

		public $tableName = '_payment_payex';
		public $usePrefix = true;

		private $payex;
		
		private $_productLines = false;
		private $_shippingCost = false;
		private $_shippingAddress = false;
		private $_billingAddress = false;

		private $purchaseOperation = 'AUTHORIZATION';		
		private $priceArgList = '';
	        private $clientIdentifier = '';
		private $additionalValues = '';
		private $externalID = '';
		private $view = 'CC';
		private $agreementRef = '';
		private $encryptionKey = SERIA_PAYMENT_PAYEX_SECRET_KEY;
		private $accountNumber = SERIA_PAYMENT_PAYEX_ACCOUNT_NUMBER;

		public function getObjectId() {
			if (!$this->id) {
				$this->save();
			}
			return array('SERIA_Payment_PayEx', 'createObject', $this->id);
		}
		
		public static function createObject($id) {
			return SERIA_Payment_PayExs::find($id);
		}
		public function setCurrency($code) {
			$this->currency = $code;
		}
		public function setLanguage($code) {
			$this->language = $code;
		}
		public function addProductLine($id, $text, $unitPrice, $vat, $count, $unitDiscount) {
			$this->_productLines[] = array($id, $text, $unitPrice, $vat, $count, $unitDiscount);
		}
		public function setShippingCost($description, $price, $vat) {
			$this->_shippingCost = array($description, $price, $vat);
		}
		public function setShippingAddress($name, $address, $zip, $city, $country = 'NO') {
			$this->_shippingAddress = array($name, $address, $zip, $city, $country);
		}
		public function setBillingAddress($name, $address, $zip, $city, $country = 'NO', $email) {
			$this->_billingAddress = array($name, $address, $zip, $city, $country, $email);
		}
		protected function beforeSave() {
			if ($this->isValid()) {
				if(is_array($this->_productLines))
					$this->productlines = serialize($this->_productLines);
				if(is_array($this->_shippingCost))
					$this->shippingcost = serialize($this->_shippingCost);
				if(is_array($this->_shippingAddress))
					$this->shippingaddress = serialize($this->_shippingAddress);
				if(is_array($this->_billingAddress))
					$this->billingaddress = serialize($this->_billingAddress);
				return true;
			} else {
				throw new SERIA_Exception('Validation error!');
			}
		}
                public function getPaymentStatus() {
			return $this->orderstatus;
		}
                public function setPaymentStatus($status) {
			$this->orderstatus = $status;
		}

                public function getTransactionId() {
			return $this->transactionid;
		}

                public function setTransactionId($orderref) {
			$this->transactionid = $orderref;
		}

		public function getCheckoutUrl($returnUrlOnSuccess, $returnUrlOnFailure) {
			if(!sizeof($this->_productLines))
				//throw new SERIA_Exception("No ProductLines are added, unable to initiate transaction.");
			if(!sizeof($this->_shippingAddress))
				//throw new SERIA_Exception("No shipping addresss set.");
			if(!sizeof($this->_billingAddress))
				//throw new SERIA_Exception("No billing address set..");

			// Hack required for payex - to start a transaction a productg line is required with total amount of all product lines		
			$totalAmount = 0;
			
			if(sizeof($this->_productLines)) {
				foreach($this->_productLines as $pline) {
					$totalAmount+=($pline[2]*$pline[4]);
				}
			}

			$returnUrlOnSuccess = htmlspecialchars(SERIA_HTTP_ROOT . '/seria/frameworks/payex/return.php?success=' . $returnUrlOnSuccess . '&failure=' . $returnUrlOnFailure);

			array_unshift($this->_productLines, array(PAYEX_SHOP_NAME, PAYEX_SHOP_DESCRIPTION, $totalAmount*100, 2500, 1, 0));
	
	                $this->clientIdentifier = "USERAGENT=".$_SERVER['HTTP_USER_AGENT'];

			$orderid = $this->getObjectId();
			$params = array(
				'accountNumber' => $this->accountNumber,
				'purchaseOperation' => $this->purchaseOperation,
				'price' => $this->_productLines[0][2],
				'priceArgList' => $this->priceArgList,
				'currency' => $this->currency,
				'vat' => $this->_productLines[0][3],
				'orderID' => "".$orderid[2],
				'productNumber' => $this->_productLines[0][0],
				'description' => $this->_productLines[0][1],
				'clientIPAddress' => "".$_SERVER['REMOTE_ADDR'],
				'clientIdentifier' => $this->clientIdentifier,
				'additionalValues' => $this->additionalValues,
				'externalID' => $this->externalID,
				'returnUrl' => $returnUrlOnSuccess,
				'view' => $this->view,
				'agreementRef' => $this->agreementRef,
				'cancelUrl' => $returnUrlOnFailure,
				'clientLanguage' => $this->language
			);
			SERIA_Base::addFramework("payex");
			$order = new order();
			$functions = new functions();
			$result = $order->initialize7($params);
			$status = $functions->checkStatus($result);
			
			if($status['code'] == "OK" && $status['errorCode'] == "OK" && $status['description'] == "OK")
			{
				// Work up an URL
				$this->setTransactionId($status["orderRef"]);
				$count=0;
				foreach($this->_productLines as $pline) {
					if($count==0) {
						// First line is already added (required by payex)
						$count++;
					} else {

						$params = array(
							'accountNumber' => $this->accountNumber,
							'orderRef' => $status["orderRef"],
							'itemNumber' => str_replace(array("\n","\r"),array("",""),$this->_productLines[$count][0]),
							'itemDescription1' => str_replace(array("\n","\r"),array("",""),$this->_productLines[$count][1]),
							'itemDescription2' => '',
							'itemDescription3' => '',
							'itemDescription4' => '',
							'itemDescription5' => '',
							'quantity' => $this->_productLines[$count][4],
							'amount' => ($this->_productLines[$count][2] * $this->_productLines[$count][4])*100,
							'vatPrice' => (($this->_productLines[$count][2]*$this->_productLines[$count][4])-(($this->_productLines[$count][2]*$this->_productLines[$count][4])/1.25))*100,
							'vatPercent' => 2500
						);

						$response = $order->addOrderLine($params);
						$count++;
					}
				}
				$this->save();
				return $status["redirectUrl"][0];
			}
			else {
                	        foreach($status as $error => $value)
                	        {
                                echo "$error, $value"."\n";
                       		 }
                	}
			return;
		}
		public function setOrderNumber($orderNumber) {
			$this->orderID = $orderNumber;
		}
		public function maintain() {
		}
	}		
?>
