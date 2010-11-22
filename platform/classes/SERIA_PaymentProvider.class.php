<?php
	define('SERIA_PAYMENT_PENDING', 0);
	define('SERIA_PAYMENT_FAILED', 100);
	define('SERIA_PAYMENT_SUCCESS', 1000);
	
	interface SERIA_PaymentProvider extends SERIA_NamedObject {
		/**
		 * Set currency code for transaction
		 * @param $code ISO4217 currency code.
		 * @return bool
		 */
		public function setCurrency($code);
		
		/**
		 * Set langunage.
		 * @param $code ISO639-1 two letter language code.
		 * @return bool
		 */
		public function setLanguage($code);
		
		/**
		 * Set order number
		 * @param $orderNumber Numeric order number for this order
		 */
		public function setOrderNumber($orderNumber);
		
		/**
		 * Add a product line to the transaction.
		 * @param $id Product ID.
		 * @param $text Description of product.
		 * @param $unitPrice Price for each unit not including VAT.
		 * @param $vat Percent VAT to add to price.
		 * @param $count Number of items.
		 * @param $unitDiscount Percent of discount.
		 * @return bool
		 */
		public function addProductLine($id, $text, $unitPrice, $vat, $count, $unitDiscount);
		
		/**
		 * Set shipping cost for transaction.
		 * @param $description Shipping type/description.
		 * @param $price Price not including VAT.
		 * @param $vat VAT percent to add to price.
		 * @return bool
		 */
		public function setShippingCost($description, $price, $vat);
		
		/**
		 * Set to which address the items is shipped to.
		 * @param $name Person/company name.
		 * @param $address Post address. Can be multi line.
		 * @param $zip Zip/Postal code.
		 * @param $city City/Postal area.
		 * @param $country Country code, defaults to NO
		 * @return bool
		 */
		public function setShippingAddress($name, $address, $zip, $city, $country = 'NO');
		
		/**
		 * Set billing address/address on invoice.
		 * @param $name Person/company name.
		 * @param $address Post address. Can be multi line.
		 * @param $zip Zip/Postal code.
		 * @param $city City/Postal area.
		 * @param $country Country code, defaults to NO
		 * @param $email Email address.
		 * @return bool
		 */
		public function setBillingAddress($name, $address, $zip, $city, $country = 'NO', $email);
		
		/**
		 * Receive payment status from object. Return value, one of:
		 * SERIA_PAYMENT_PENDING (order not processed),
		 * SERIA_PAYMENT_FAILED (order processed with error)
		 * SERIA_PAYMENT_SUCCESS (order processed successfully)
		 * @return int
		 */
		public function getPaymentStatus();
		
		/**
		 * Receive transaction number for payment. This number must be the providers reference for the transaction.
		 * This number is only available when payment status is SERIA_PAYMENT_SUCCESS.
		 * @return string
		 */
		public function getTransactionId();
		
		/**
		 * Save order in database and check for errors.
		 * @throws SERIA_Exception If there is any errors.
		 * @return bool
		 */
		public function save();
		
		/**
		 * Get URL for checkout. The user must be redirected to the returned URL for processing payment.
		 * @param $returnUrlOnSuccess The URL the user is returned to if the payment was successfull.
		 * @param $returnUrlOnFailure The URL the user is returned to if the payment was unsuccessfull.
		 * @return unknown_type
		 */
		public function getCheckoutUrl($returnUrlOnSuccess, $returnUrlOnFailure);
		
		/**
		 * This method will be run every time maintain is executed. If there is nothing to be done regulary,
		 * this method may be defined empty.
		 * @return string
		 */
		public function maintain();
	}
?>
