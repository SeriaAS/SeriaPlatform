<?php
	class SERIA_Payment_Dibs extends SERIA_ActiveRecord implements SERIA_PaymentProvider {
		public $tableName = '_payment_dibs';
		public $usePrefix = true;
		
		public function getObjectId() {
			if (!$this->id) {
				$this->save();
			}
			return array('SERIA_Payment_Dibs', 'createObject', $this->id);
		}
		
		public static function createObject($id) {
			return SERIA_Payment_Dibses::find($id);
		}
		
		public function setOrderNumber($number) {
			$this->ordernumber = $number;
		}
		
		public function setCurrency($code) {
			$this->currency = $code;
		}
		
		public function setLanguage($code) {
			$this->language = $code;
		}
		
		public function addProductLine($id, $text, $unitPrice, $vat, $count, $unitDiscount) {
			$amount = $count * $unitPrice;
			$discount = $amount * ($unitDiscount / 100);
			$amount -= $discount;
			$amount = $amount * (1 + $vat / 100);
			$this->amount +=  $amount * 100;
		}
		
		public function setShippingCost($description, $price, $vat) {
			$this->shippingcost = ($price * (1 + $vat / 100)) * 100;
		}
		
		public function setShippingAddress($name, $address, $zip, $city, $country = 'NO') {
		}
		public function setBillingAddress($name, $address, $zip, $city, $country = 'NO', $email) {
		}
		
		public function getPaymentStatus() {
			return $this->status;
		}
		public function getTransactionId() {
			return $this->transactionid;
		}
		
		protected function beforeSave() {
			return true;
		}
		public function getCheckoutUrl($returnUrlOnSuccess, $returnUrlOnFailure) {
			$this->successurl = $returnUrlOnSuccess;
			$this->failureurl = $returnUrlOnFailure;
			$this->save();
			
			$url = SERIA_HTTP_ROOT . '/seria/platform/payment/dibs/topayment.php?id=' . $this->id;
			
			return $url;
		}
		
		public function maintain() {
		}
		
		public static function calculateAuthCgiMd5Key($params) {
			$keyparams = 'merchant=' . $params['merchant'] . '&orderid=' . $params['orderid'] .
			            '&currency=' . $params['currency'] . '&amount=' . $params['amount'];
			return md5(SERIA_PAYMENT_DIBS_MD5_KEY2 . md5(SERIA_PAYMENT_DIBS_MD5_KEY1 . $keyparams));
		}
		public static function calculateCallbackMd5Sum($transactionId, $amount, $currency) {
			$keyparams = 'transact=' . $transactionId . '&amount=' . $amount . '&currency=' . $currency;
			return md5(SERIA_PAYMENT_DIBS_MD5_KEY2 . md5(SERIA_PAYMENT_DIBS_MD5_KEY1 . $keyparams));
		}
		
		public static function convertToCurrencyCode($currency) {
			$currencyCodes = array(
				'DKK' => 208,
				'EUR' => 978,
				'USD' => 840,
				'GBP' => 826,
				'SEK' => 752,
				'AUD' => 036,
				'CAD' => 124,
				'ISK' => 352,
				'JPY' => 392,
				'NZD' => 554,
				'NOK' => 578,
				'CHF' => 756,
				'TRY' => 949
			);
			
			if (!$code = $currencyCodes[strtoupper($currency)]) {
				throw new SERIA_Exception('Payment currency is not supported');
			}
			
			return $code;
		}
	}
?>
