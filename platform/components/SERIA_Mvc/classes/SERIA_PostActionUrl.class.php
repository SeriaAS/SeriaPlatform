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
	 * Returns an ajax-post code for the onclick attribute. (Not encoded)
	 * @param $refresh boolean Whether the page should be refreshed after the action.
	 * @return string Javascript code
	 */
	public function onclickCode($refresh=true)
	{
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
		$js = 'var returnmsg = jQuery.ajax('.$settings.'); if (returnmsg.status == 200) { returnmsg = returnmsg.responseText; if (returnmsg != "OK") alert(returnmsg); } else { alert("The HTTP request for this action failed!"); } ';
		if ($refresh)
			$js .= ' location.href = '.SERIA_Lib::toJSON(SERIA_Url::current()->__toString()).';';
		$js .= ' return false;';
		return $js;
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
				SERIA_Template::override('text/plain', 'OK');
			else if ($this->error)
				SERIA_Template::override('text/plain', $this->error);
			else
				SERIA_Template::override('text/plain', _t('The action failed with no specified reason!'));
			die();
		} else {
			if ($url === null)
				$url = SERIA_Url::current()->__toString();
			else if ($url instanceof SERIA_Url)
				$url = $url->__toString();
			SERIA_Base::redirectTo($url);
		}
	}
}
