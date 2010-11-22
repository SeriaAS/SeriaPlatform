<?php

	/**
	*	Usage:
	*
	*	SERIA_Captcha generates a number between min and max specified in the
	*	constructor. This number will be valid for the specified number of seconds
	*	defined in checkNumeric argument two.
	*
	*	Example:
	*
	*	// GENERATING THE FORM
	*	$c = new SERIA_Captcha('comments', 1000, 9999);
	*	
	*	// create a picture containing the numbers $c->getNumber();
	*	output_image($c->getNumber());
	*
	*
	*
	*	// VALIDATING THE FORM
	*
	*	$c = new SERIA_Captcha('comments', 1000, 9999);
	*
	*	if(!$c->checkNumber($_POST['captcha']))
	*		echo "SORRY! You may not be human!";
	*/

	class SERIA_Captcha
	{
		private $key;
		/**
		*	If you do not specify a key, then SERIA_Captcha will use the 
		*	current time and date to create captchas. A captcha returned
		*	will be valid for between 60 and 90 minutes.
		*
		*	@param $key		The area of the website where this is used, for example 'comments', or $_SERVER['REQUEST_URI'].
		*/
		public function __construct($key = NULL, $min = NULL, $max = NULL)
		{
			$max===NULL && $max = 999999;
			$min===NULL && $min = 100000;

			if($key === NULL)
				$this->key = md5(SERIA_SALT);
			else
				$this->key = md5($key.SERIA_SALT);
			$this->min = $min;
			$this->max = $max;
		}

		/**
		*	Returns a number calculated from the value in $this->key
		*/
		protected function getNumericSalt()
		{
			$val = 1;
			$max = 2147483647; // a quite big prime number :-)
			$l = strlen($this->key);
			for($i = 0; $i < $l; $i++)
			{
				$val *= ord($this->key[$i]+1);
				$val = $val % $max + 1;
			}
			return $val;
		}

		protected function hashNumberGen($data, $min, $max)
		{
			$hash = sha1($data, true);
			$b1 = ord($hash[0]);
			$b2 = ord($hash[1]);
			$b3 = ord($hash[2]);
			$b4 = ord($hash[3]);
			$raw = $b1 + ($b2 << 8) + ($b3 << 16) + ($b4 << 24);
			if ($raw < 0)
				$raw = 0 - $raw;
			$cutoff = 1 + $max - $min;
			return ($raw % $cutoff) + $min;
		}
		protected function calculate($seed)
		{
			return $this->hashNumberGen($seed, $this->min, $this->max);
		}

		public function getNumber()
		{
			$time = intval(time()/10);
			return $this->calculate($this->getNumericSalt()+$time, $this->min, $this->max);
		}

		// check if the provided number could have been generated any time the last 90 minutes.
		public function checkNumber($value, $lifetime=5400)
		{
			$time = intval(time()/10);
			for($i = 0; $i < $lifetime; $i++)
				if($value == $this->calculate($this->getNumericSalt()+$time-$i, $this->min, $this->max))
					return true; // this is a legal value

			return false; // no legal values found the last 90 minutes
		}
	}
