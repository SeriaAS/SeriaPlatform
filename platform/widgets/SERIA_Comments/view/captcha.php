<?php
	require_once(dirname(__FILE__).'/recaptcha-php-1.10/recaptchalib.php');
	
	$publickey = "6LeRGAYAAAAAAKihv6DOsbVXMo7wjuTeke2T1owG"; // you got this from the signup page
	$privatekey = "6LeRGAYAAAAAAO7x8v_68fZO0GzXlMPATh4YMHAi";
	
	# the response from reCAPTCHA
	$resp = null;
	# the error code from reCAPTCHA, if any
	$error = null;	

	
	echo "<form action='' method='post'>";
	echo recaptcha_get_html($publickey, $error);
	echo "</form>";

	if($_POST) {
		$resp = recaptcha_check_answer ($privatekey, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		if ($resp->is_valid) {
			echo "YESSUCCESS";
		} else {
			echo "ERROR CAPTFAULERFAILUERLOLOLOLCHA said: " . $resp->error;
		}
	}
