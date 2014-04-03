<?php
/**
*	Special class that behaves as a normal WebAppRequest, except it allows faking the content.
*/
class WebAppRequestFake extends WebAppRequest {
	public function __construct($content, $httpCode) {
		$this->content = $content;
		$this->httpCode = $httpCode;
		$this->state = new WebAppState();
		$this->caching = array(
			'limiter' => SERIA_ProxyServer::CACHE_NOCACHE,
			'expires' => time(),
		);
		SERIA_ProxyServer::applyState($this->caching);
	}

	public function __toString() {
		return '<div style="border: 2px solid red; background-color: white; display: block; margin: 0px; padding: 10px;"><strong>WebApp</strong><br>'.$this->content.'</div>';
	}
}
