<?php

/**
 *
 * This does mostly the same as SERIA_ActionUrl except that this
 * require actions to be done as a POST-request. It is mostly backwards
 * compatible with SERIA_ActionUrl except that this REQUIRES a redirect
 * after a successful action. (It will indeed tell you that with
 * SERIA_DEBUG turned on unless you do a redirect by calling
 * $this->actionReturn() or SERIA_Base::redirectTo($url)!)
 *
 * @author Jan-Espen Pettersen
 *
 */
class SERIA_PostActionUrl extends SERIA_ActionUrl
{
	protected $type = null;

	/*
	 * This part is code for detection of missing redirect after
	 * POST success.
	 */
	protected $debug_actionReturn = false;
	public function _debug_templateOutputHandler()
	{
		if ($this->invoked() && !$this->debug_actionReturn) {
			$error = 'A POST-action has been done ('.$this->_name.'). You must ALWAYS call ->actionReturn after  invokation of a post-action (either with error or success)!';
			if ($this->type == 'ajax') {
				/*
				 * Send the error message back to the caller
				 */
				$this->success = false;
				$this->error = $error;
				$this->actionReturn();
			} else
				throw new SERIA_Exception($error);
		}
	}
	protected function strictReceptionDebugging()
	{
		SERIA_Hooks::listen('SERIA_Template::outputHandler', array($this, '_debug_templateOutputHandler'));
	}

	/**
	 *
	 * POST these fields to the current URL to invoke directly in the webbrowser (not by ajax).
	 */
	public function getPostInvokeParams()
	{
		$data = array(
			$this->_name => $this->_data,
		);
		if ($this->_state)
			$data[$this->_name.'-s'] = serialize($this->_state);
		return $data;
	}

	public function invoked()
	{
		if((isset($_POST[$this->_name]) && $_POST[$this->_name] == $this->_data)) {
			if(isset($_POST[$this->_name.'-s']))
				$this->_state = unserialize($_POST[$this->_name.'-s']);
			else
				$this->_state = NULL;
			if (isset($_POST['type']))
				$this->type = $_POST['type'];
			else
				$this->type = 'form';
			if (defined('SERIA_DEBUG') && SERIA_DEBUG)
				$this->strictReceptionDebugging();
			return true;
		} else if (parent::invoked()) {
			/*
			 * This has been invoked the old way by GET-request. Do
			 * an automatic POST the new way.
			 */
			$url = SERIA_Url::current();
			$url = $this->removeFromUrl($url);
			/* Bug in the above function. State is left */
			if ($url->getParam($this->_name.'-s'))
				$url->unsetParam($this->_name.'-s');

			$params = array('url' => $url->__toString(), 'name' => $this->_name, 'data' => $this->_data);
			if ($this->_state)
				$params['state'] = serialize($this->_state);
			$formUrl = SERIA_Meta::manifestUrl('mvc', 'action', $params);
			SERIA_Base::redirectTo($formUrl->__toString());
		}
	}

	/**
	 *
	 * Returns an array ( 'url' => $url, 'data' => array(post data...)).
	 */
	public function getAjaxCallData()
	{
		$data = $this->getPostInvokeParams();
		$data['type'] = 'ajax';
		return array(
			'url' => SERIA_Url::current()->__toString(),
			'data' => $data
		);
	}

	public function javascriptCode($errorHandler='alert', $successHandler='null')
	{
		$nullHandler = 'function () {}';
		if (!$errorHandler)
			$errorHandler = $nullHandler;
		if (!$successHandler)
			$successHandler = $nullHandler;
		SERIA_ScriptLoader::loadScript('jQuery');
		$settings = $this->getAjaxCallData();
		$settings['async'] = false;
		$settings['dataType'] = 'text';
		$settings['type'] = 'POST';
		$settings = SERIA_Lib::toJSON($settings);
		$settings = str_replace("\r", ' ', $settings);
		$settings = str_replace("\n", ' ', $settings);
		return '(function () { var returnmsg = jQuery.ajax('.$settings.'); if (returnmsg.status == 200) { returnmsg = returnmsg.responseText; if (returnmsg == "OK") { ('.$successHandler.')(); } else { ('.$errorHandler.')(returnmsg); } } else { ('.$errorHandler.')("The HTTP request for this action failed!"); } })();';
	}
	/**
	 *
	 * Returns an ajax-post code for the onclick attribute. (Not encoded)
	 * @param $refresh boolean Whether the page should be refreshed after the action. Default true.
	 * @param $cancelBubble boolean Cancel the bubble (for usage with onclick attributes). Default true.
	 * @param $returnFalse boolean Return false (for usage with onclick attributes). Default true.
	 * @param $errorHandler string Javascript function, default 'alert'.
	 * @param $successHandler string Javascript function, default no handling.
	 * @return string Javascript code
	 */
	public function onclickCode($refresh=true, $cancelBubble=true, $returnFalse=true, $errorHandler='alert', $successHandler=null)
	{
		$nullHandler = 'function () {}';
		if (!$errorHandler)
			$errorHandler = $nullHandler;
		if (!$successHandler)
			$successHandler = $nullHandler;
		SERIA_ScriptLoader::loadScript('jQuery');
		$data = array(
			$this->_name => $this->_data,
			'type' => 'ajax'
		);
		if ($this->_state)
			$data[$this->_name.'-s'] = serialize($this->_state);
		$settings = array(
			'url' => SERIA_Url::current()->__toString(),
			'async' => false,
			'dataType' => 'text',
			'type' => 'POST',
			'data' => $data
		);
		$settings = SERIA_Lib::toJSON($settings);
		$settings = str_replace("\r", ' ', $settings);
		$settings = str_replace("\n", ' ', $settings);
		$js = '';
		if ($cancelBubble)
			$js .= ' var event = arguments[0] || window.event; event.cancelBubble = true; if (event.stopPropagation) event.stopPropagation();';
		$js .= ' var returnmsg = jQuery.ajax('.$settings.'); if (returnmsg.status == 200) { returnmsg = returnmsg.responseText; if (returnmsg == "OK") { ('.$successHandler.')(); } else { ('.$errorHandler.')(returnmsg); } } else { ('.$errorHandler.')("The HTTP request for this action failed!"); } ';
		if ($refresh)
			$js .= ' location.href = '.SERIA_Lib::toJSON(SERIA_Url::current()->__toString()).';';
		if ($returnFalse)
			$js .= ' return false;';
		return trim($js);
	}

	/**
	 *
	 * Returns the call type (ie. "ajax" or "form") when this action has been invoked, otherwise null.
	 * @return string Example: "ajax".
	 */
	public function type()
	{
		return $this->type;
	}

	/*
	 * Works around bugs in SERIA_Template.
	 */
	protected static function overrideContent($contentType, $content)
	{
		if (!SERIA_Template::$disabled)
			SERIA_Template::override($contentType, $content);
		else {
			header('Content-Type: '.$contentType);
			while (ob_end_clean());
			die($content);
		}
		die();
	}

	/**
	 *
	 * This function automatically redirects the user or returns the status
	 * by ajax depending on how this action was invoked. Please use this function
	 * after setting the action successful state.
	 *
	 * @param string $url Redirect to this url if this is a form submit invoke-type. No effect for ajax returns.
	 */
	public function actionReturn($url=null)
	{
		$this->debug_actionReturn = true;
		if ($this->type() == 'ajax') {
			if ($this->success)
				self::overrideContent('text/plain', 'OK');
			else if ($this->error)
				self::overrideContent('text/plain', $this->error);
			else
				self::overrideContent('text/plain', _t('The action failed with no specified reason!'));
			die();
		} else {
			if ($url === null)
				$url = SERIA_Url::current()->__toString();
			else if ($url instanceof SERIA_Url)
				$url = $url->__toString();
			SERIA_Base::redirectTo($url);
		}
	}

	/**
	 *
	 * If the action is a form submit in a webbrowser you can in rare cases
	 * continue processing PHP-code after this. This cancels the requirement
	 * of calling ->actionReturn. This is not available for ajax calls.
	 */
	public function actionContinue()
	{
		if ($this->type() == 'form') {
			$this->debug_actionReturn = true;
			return;
		}
		throw new SERIA_Exception('Action continue is not available for this invoke-type ('.$this->type().')!');
	}
}
