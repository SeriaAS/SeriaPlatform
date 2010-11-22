<?php

/**
 * 
 * Action-url class that can transfer the state parameters to the action invoke.
 * (The __toString method is overridden)
 *
 * @author Jan-Espen
 *
 */
class SERIA_ActionAuthenticationStateUrl extends SERIA_ActionUrl
{
	protected $state;

	function __construct($name, $data, SERIA_AuthenticationState $state)
	{
		parent::__construct($name, $data);
		$this->state = $state;
	}

	public function __toString()
	{
		return $this->state->stampUrl(parent::__toString());
	}
}