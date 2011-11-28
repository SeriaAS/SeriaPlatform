<?php

class SERIA_OutboardEmailTpl
{
	protected $subject = null;
	protected $from = null;
	protected $to = null;
	protected $content = null;
	protected $contentType = 'text/plain';
	protected $charset = 'utf8';

	public function setSubject($title)
	{
		$this->subject = $title;
	}
	public function setFrom($hdrFrom)
	{
		$this->from = $hdrFrom;
	}
	public function setTo($hdrTo)
	{
		$this->to = $hdrTo;
	}
	public function setContentType($contentType, $charset=null)
	{
		$this->contentType = $contentType;
		if ($charset !== null)
			$this->charset = $charset;
	}

	public function parse($filename, $data)
	{
		ob_start();
		include($filename);
		$this->content = ob_get_clean();
		if (strpos($this->content, "\r\n") === false && strpos($this->content, "\n\r") === false) {
			$this->content = str_replace("\r", "\n", $this->content);
			$this->content = str_replace("\n", "\r\n", $this->content);
		}
	}

	public function send($addrTo=null)
	{
		$missing = array();
		if ($this->subject === null)
			$missing[] = 'subject';
		if ($this->from === null)
			$missing[] = 'from';
		if ($this->to === null)
			$missing[] = 'to';
		if ($this->content === null)
			$missing[] = 'content';
		if ($missing)
			throw new SERIA_Exception('Missing email fields: '.implode(', ', $missing));
		$headers = array(
			'Content-Type' => $this->contentType.'; charset='.$this->charset
		);
		if ($addrTo === null)
			$addrTo = $this->to;
		if (($pos = strrpos($addrTo, '<')) !== false) {
			$email = substr($addrTo, $pos + 1);
			if (substr($email, -1, 1) != '>')
				throw new SERIA_Exception('Invalid to-addr: ...<'.$addrTo);
			$email = substr($email, 0, -1);
			if (!$email)
				throw new SERIA_Exception('Invalid to-addr: ...<>');
			$addrTo = $email;
		}
		if (SERIA_IsInvalid::email($this->from))
			$headers['From'] = $this->from;
		else
			$headers['From'] = '<'.$this->from.'>';
		if (SERIA_IsInvalid::email($this->to))
			$headers['To'] = $this->to;
		else
			$headers['To'] = '<'.$this->to.'>';
		foreach ($headers as $name => &$value)
			$value = $name.': '.$value;
		unset($value);
		$headers = implode("\r\n", $headers);
		if ($headers)
			$headers .= "\r\n";
		else
			$headers = '';
		if ($this->contentType == 'plain/text')
			$this->content = wordwrap($this->content, 70);
		mail($addrTo, $this->subject, $this->content, $headers);
	}
}