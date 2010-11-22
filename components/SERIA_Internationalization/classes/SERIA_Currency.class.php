<?php
	/**
	*	Class for working with different currencies in Seria Platform.
	*
	*	Ideas:
	*		- Provide customers with currency conversion tools live from a Seria API.
	*/
	class SERIA_Currency
	{
		protected $_amount, $_symbol;
		function __construct($amount, $symbol=false)
		{
			$this->_amount = $amount;
			if($symbol === false)
				$this->_symbol = SERIA_CURRENCY;
			else
				$this->_symbol = $symbol;
		}

		public function getAmount()
		{
			return $this->_amount;
		}

		public function getSymbol()
		{
			return $this->_symbol;
		}

		function convertTo($symbol, $rate=false)
		{
			if($rate === false) 
				throw new SERIA_Exception('Exchange rates not yet implemented in Seria Platform. Should be done on first request.');

			$amount = $this->_amount * $rate;
			return new SERIA_Currency($amount, $symbol);
		}

		function subtract(SERIA_Currency $currency)
		{
			if($this->_symbol == $currency->getSymbol())
			{
				$this->_amount -= $currency->getAmount();
			}
			else
				throw new SERIA_Exception('Unable to calculate between different currencies. Use $currency->exchangeTo($symbol, $rate).)');
		}
	}
