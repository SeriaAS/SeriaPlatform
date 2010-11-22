<?php

class SERIA_TwitterStatus
{
	protected $sys  = null;

	public function __construct(SERIA_TwitterSys $twittersys)
	{
		$this->sys = $twittersys;
	}

	public function update($text)
	{
		$xml = $this->sys->signedRequest('https://twitter.com/statuses/update.xml', 'POST', array(
			'status' => $text
		));
		if ($xml === null)
			throw new Exception(_t('Twitter status update failed.'));
	}
}

?>