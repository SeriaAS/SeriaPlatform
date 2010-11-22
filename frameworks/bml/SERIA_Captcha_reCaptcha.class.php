<?php

require_once(dirname(__FILE__).'/recaptcha-php-1.10/recaptchalib.php');

class SERIA_Captcha_reCaptcha
{
	protected $privateKey;
	protected $publicKey;
	protected $error = null;

	public function __construct()
	{
	}

	public function setKeyPair($privateKey, $publicKey)
	{
		$this->privateKey = $privateKey;
		$this->publicKey = $publicKey;
	}

	/**
	 * Get the HTML-code for the captcha challenge.
	 *
	 * @return unknown_type
	 */
	public function getChallenge()
	{
		return recaptcha_get_html($this->publicKey, $this->error);
	}

	/**
	 * Check the validity of the answer given.
	 *
	 * @return string TRUE on correct answer, error message otherwise.
	 */
	public function checkAnswer()
	{
		$resp = recaptcha_check_answer($this->privateKey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		if (!$resp->is_valid)
			return _t('Incorrect answer.');
		return true;
	}
}

?>