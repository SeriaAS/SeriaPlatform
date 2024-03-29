<?php

/**
 * Group web-requests together so that they can be run in parallel.
 *
 * @author Jan-Espen Pettersen
 *
 */
class SERIA_WebBrowsers
{
	protected $webbrowsers = array();
	protected $prepared = false;
	protected $timeout = 120;

	const PROGRESS_READ = 'read';
	const PROGRESS_FINISHED = 'finished';
	protected $progress = null;
	protected static $progressText = array(
			self::PROGRESS_READ => 'Reading data',
			self::PROGRESS_FINISHED => 'Finished'
	);
	
	public function setProgressCallback($progress)
	{
		$this->progress = $progress;
	}

	public function sendProgress($progress, $url)
	{
		if (!$this->progress)
			return;
		call_user_func_array($this->progress, array($progress, $url, self::$progressText[$progress]));
	}

	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}
	public function getTimeout()
	{
		return $this->timeout;
	}

	public function addWebBrowser(SERIA_WebBrowser &$webbrowser)
	{
		if ($this->prepared)
			throw new SERIA_Exception('This group of webbrowsers is already doing fetching. Too late to add.');
		$record = array('data' => '');
		$record['webbrowser'] =& $webbrowser;
		$this->webbrowsers[] =& $record;
	}

	protected function &prepareAll($deadline)
	{
		if ($this->prepared)
			throw new SERIA_Exception('A group of webbrowsers can only be used once.');
		$sockets = array();
		$onlySockets = array();
		foreach ($this->webbrowsers as &$record) {
			$record['webbrowser']->requestDataTimeout = $this->timeout;
			try {
				$tmavail = $deadline - time();
				if ($tmavail < 0)
					throw new SERIA_Exception('No time available for connecting to remote');
				if ($tmavail == 0)
					$tmavail = 1;
				$record['webbrowser']->send($tmavail);
				$onlySockets[] = $record['webbrowser']->getSocket();
			} catch (SERIA_Exception $e) {
				$record['exception'] = $e;
				$record['error'] = $e->getMessage();
				$record['data'] = false;
			}
			$sockets[] = array(
				'record' => &$record,
				'socket' => $record['webbrowser']->getSocket()
			);
		}
		$prepared = true;
		return array(
			'assocSockets' => &$sockets,
			'sockets' => &$onlySockets
		);
	}
	public function fetchAll($cacheControl=false)
	{
		$startime = time();
		$deadline = $startime + ($this->timeout / 2);
		if ($deadline <= $starttime)
			$deadline = $starttime + 1;
		$sockets =& $this->prepareAll($deadline);
		$write = array();
		$remainingSockets = $sockets['sockets'];
		$remaining = count($remainingSockets);
		$timedOut = false;
		while ($remaining > 0) {
			if (!$timedOut && time() > ($startime + $this->timeout + 1))
				$timedOut = true;
			if (SERIA_DEBUG)
				SERIA_Base::debug('Waiting for events on sockets: '.implode(', ', $remainingSockets));
			$read = $remainingSockets;
			$exceptions = $remainingSockets;
			$res = stream_select($read, $write, $exceptions, $timedOut ? 0 : $this->timeout);
			if (SERIA_DEBUG)
				SERIA_Base::debug('Read: '.implode(', ', $read).'; Exception: '.implode(', ', $exceptions));
			if ($res === 0)
				break;
			if ($res === false)
				throw new SERIA_Exception('socket_select failed.');
			foreach ($remainingSockets as $remainingKey => $socket) {
				if (array_search($socket, $read) !== false || array_search($socket, $exceptions) !== false) {
					foreach ($sockets['assocSockets'] as &$record) {
						if ($record['socket'] == $socket) {
							try {
								$chunk = $record['record']['webbrowser']->fetch(4096, true);
							} catch (Exception $e) {
								SERIA_Base::debug('SERIA_WebBrowsers received exception from fetch method: '.$e->getMessage());
								$record['record']['error'] = $e->getMessage();
								$record['record']['exception'] = $e;
								$record['record']['data'] = false;
								$chunk = false;
							}
							if ($chunk !== false) {
								$restarted = $record['record']['webbrowser']->getSocket();
								if ($restarted !== null && $restarted != $socket) {
									SERIA_Base::debug('Socket '.$socket.' has been restarted as '.$restarted.' (prob. redirect)');
									$record['socket'] = $restarted;
									$remainingSockets[$remainingKey] = $restarted;
								}
								SERIA_Base::debug('Read '.strlen($chunk).' bytes from '.$socket);
								$this->sendProgress(self::PROGRESS_READ, $record['record']['webbrowser']->url);
								$record['record']['data'] .= $chunk;
							} else {
								SERIA_Base::debug('Terminated read from '.$socket);
								$this->sendProgress(self::PROGRESS_FINISHED, $record['record']['webbrowser']->url);
								if (!isset($record['record']['error']) || !$record['record']['error'])
									$record['record']['completedAt'] = microtime(true);
								if ($cacheControl && $record['record']['webbrowser']->method == 'GET') {
									$hPragma = $record['record']['webbrowser']->responseHeaders['Pragma'];
									$hCacheControl = $record['record']['webbrowser']->responseHeaders['Cache-Control'];
									if (!$hPragma || $hPragma != 'no-cache') {
										$values = array();
										$cachePage = true;
										if ($hCacheControl) {
											$tValues = explode(',', $hCacheControl);
											foreach($tValues as $value) {
												$data = explode('=', $value);
												$values[trim($data[0])] = (isset($data[1]) ? trim($data[1]) : true);
											}
											if ($values['no-store'] || $values['no-cache']) $cachePage = false;
										}
										if ($cachePage) {
											$cache = new SERIA_Cache('WebBrowser');
//											$cache->set($record['record']['webbrowser']->url, $record['record']['data'],);
										}
									}
								}
								unset($remainingSockets[$remainingKey]);
								$remaining--;
								SERIA_Base::debug($remaining.' sockets remaining.');
							}
						}
					}
					unset($record);
				}
			}
			foreach ($sockets['assocSockets'] as &$record) {
				$remainingKey = array_search($record['socket'], $remainingSockets);
				if ($remainingKey !== false) {
					if ($record['record']['webbrowser']->getSocket() === null) {
						SERIA_Base::debug('Terminating reads for socket '.$record['socket'].' to empty the buffer.');
						while (($chunk = $record['record']['webbrowser']->fetch())) {
							SERIA_Base::debug('Read '.strlen($chunk).' bytes from '.$socket);
							$this->sendProgress(self::PROGRESS_READ, $record['record']['webbrowser']->url);
							$record['record']['data'] .= $chunk;
						}
						$this->sendProgress(self::PROGRESS_FINISHED, $record['record']['webbrowser']->url);
						$record['record']['completedAt'] = microtime(true);
						unset($remainingSockets[$remainingKey]);
						$remaining--;
						SERIA_Base::debug($remaining.' sockets remaining.');
					}
				}
			}
			unset($record);
		}
		/*
		 * Build the return
		 */
		$returning = array();
		foreach ($sockets['assocSockets'] as &$record) {
			if (isset($record['record']['completedAt']))
				$returning[] = array(
					'webbrowser' => &$record['record']['webbrowser'],
					'data' => &$record['record']['data'],
					'completedAt' => $record['record']['completedAt']
				);
			else if (isset($record['record']['error']))
				$returning[] = array(
					'webbrowser' => &$record['record']['webbrowser'],
					'error' => $record['record']['error'], /* There could be an exception also, but not passed back from here */
					'data' => false
				);
			else
				$returning[] = array(
					'webbrowser' => &$record['record']['webbrowser'],
					'partial' => &$record['record']['data'],
					'data' => false
				);
		}
		return $returning;
	}
}
