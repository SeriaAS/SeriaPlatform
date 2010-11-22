<?php

class PowerpointConverterJobInformationFile
{
	protected static $rpcServiceName = 'MSPowerpointSlides';
	protected static $rpcClient = null;
	protected $remoteFileId;
	protected $vars = null;

	public function __construct($remoteFileId)
	{
		$this->remoteFileId = $remoteFileId;
		if (self::$rpcClient === null)
			self::$rpcClient = new SERIA_RPCFileTransfer(self::$rpcServiceName);
		$baseUrl = self::$rpcClient->getBaseUrl();
		$contents = SERIA_WebBrowser::fetchUrlContents($baseUrl.'/files/dyn/filetranscoding/powerpoint/status'.$remoteFileId.'.txt');
		if ($contents) {
			try {
				SERIA_Base::addFramework('powerpoint');
				$vars = SERIA_MSPowerPointConverterStatus::parse($contents);
				$this->vars = array();
				foreach ($vars as $name => $value)
					$this->vars[$name] = unserialize($value);
			} catch (Exception $e) {
				/* OOps: corrupted? Falling back to rpc. */
			}
		}
	}

	public function get($name)
	{
		if ($this->vars !== null) {
			if (isset($this->vars[$name])) {
				return $this->vars[$name];
			} else
				return null;
		} else
			return self::$rpcClient->getMeta($this->remoteFileId, $name);
	}
}

class PowerpointConverterJobInformation
{
	protected static $powerpoint_bin = null;
	protected $id;
	protected $_row;
	protected $_remote = null;
	protected $_data;

	public function __construct($id)
	{
		$this->id = $id;
		$this->_row = SERIA_Base::db()->query('SELECT * FROM {file_transcode_queue} WHERE id = :id', array('id' => $id))->fetch(PDO::FETCH_ASSOC);
		if (!$this->_row)
			throw new SERIA_NotFoundException('Powerpoint job does not exist.');
		if ($this->_row['transcoder'] != 'PowerpointSlides')
			throw new SERIA_Exception('This is not a Powerpoint job.');
		if ($this->_row['data'])
			$this->_row['data'] = unserialize($this->_row['data']);
		else
			$this->_row['data'] = array();
		if ($this->_row['status'] == SERIA_FileTranscoder::STATUS_COMPLETED) {
			$this->_data = array(
				'description' => _t('Completed.'),
				'progress' => 100
			);
			return;
		}
		if (!isset($this->_row['data']['remoteFileId'])) {
			if ($this->_row['status'] == SERIA_FileTranscoder::STATUS_FAILED ||
			    $this->_row['status'] == SERIA_FileTranscoder::STATUS_RESTART) {
			    if (isset($this->_row['message']) && $this->_row['message'])
			    	$description = $this->_row['message'];
			    else
			    	$description = _t('Failed.');
				$this->_data = array(
					'description' => $description,
					'progress' => 0
				);
				return;
			}
			if ($this->_row['status'] == SERIA_FileTranscoder::STATUS_TRANSCODING) {
				$description = _t('Uploading file to converter...');
				$percent = 15;
			} else {
				$description = _t('Waiting in queue for converting.');
				$percent = 0;
			} 
			$this->_data = array(
				'description' => $description,
				'progress' => $percent
			);
			return;
		}
		$totalSlides = $this->_row['data']['numberOfSlides'];
		try {
			self::$powerpoint_bin = new PowerpointConverterJobInformationFile($this->_row['data']['remoteFileId']);
			if ($totalSlides === null)
				$totalSlides = self::$powerpoint_bin->get('powerpoint_num_slides');
			if ($this->_row['status'] == SERIA_FileTranscoder::STATUS_FAILED ||
			    $this->_row['status'] == SERIA_FileTranscoder::STATUS_RESTART)
				$exc = self::$powerpoint_bin->get('powerpoint_exception');
			if ($totalSlides) {
				$cstatus = self::$powerpoint_bin->get('powerpoint_converted');
				if (!$cstatus) {
					$convSlides = self::$powerpoint_bin->get('powerpoint_latest_converted_slide');
					$dstatus = 0;
				} else {
					$file = SERIA_File::createObject($this->_row['file_id']);
					$dstatus = $file->getMeta('powerpoint_downloaded_from_remote');
					SERIA_Base::debug('Recorded latest downloaded slide: '.$dstatus);
				}
				$exc = false;
			}
			if ($exc) {
				$this->_data = array(
					'description' => _t('Failed: %EXC%', array('EXC' => $exc)),
					'progress' => 0
				);
				return;
			}
			if ($this->_row['status'] == SERIA_FileTranscoder::STATUS_FAILED ||
			    $this->_row['status'] == SERIA_FileTranscoder::STATUS_RESTART) {
				$this->_data = array(
					'description' => _t('Failed.'),
					'progress' => 0
				);
				return;
			}
			if ($totalSlides > 0)
				$percent = 20 + floor(($convSlides * 50) / $totalSlides);
			else
				$percent = 20;
			if ($cstatus) {
				if (!$dstatus) {
					$description = _t('Converted, but not yet downloaded.');
					$percent = 70;
				} else if ($dstatus == $totalSlides) {
					$description = _t('Almost finished.');
					$percent = 99;
				} else {
					$description = _t('Downloading slides (%DOWN%/%TOTAL%)', array('DOWN' => $dstatus, 'TOTAL' => $totalSlides));
					if ($totalSlides > 0)
						$percent = 90 + floor(($dstatus * 10) / $totalSlides);
					else
						$percent = 90;
				}
			} else {
				if ($totalSlides)
					$description = _t('Converting slides (%CONV%/%TOTAL%)', array('CONV' => $convSlides, 'TOTAL' => $totalSlides));
				else
					$description = _t('Starting convert on remote');
			}
			$this->_data = array(
				'description' => $description,
				'progress' => $percent
			);
		} catch (SERIA_AccessDeniedException $e) {
			$this->_data = array(
				'description' => _t('You must add RPC access to a converter server for Powerpoint-files.'),
				'progress' => 0
			);
		}
	}

	public static function createFromFileId($file_id)
	{
		return new self(SERIA_Base::db()->query('SELECT id FROM {file_transcode_queue} WHERE file_id = :file_id', array('file_id' => $file_id))->fetch(PDO::FETCH_COLUMN, 0));
	}

	public function get($name)
	{
		switch ($name) {
			case 'isRemote':
				return ($this->_remote !== null);
		}
		if (isset($this->_row[$name]))
			return $this->_row[$name];
		if ($this->_remote !== null && isset($this->_remote[$name]))
			return $this->_remote[$name];
		if (isset($this->_data[$name]))
			return $this->_data[$name];
		throw new SERIA_Exception('Field does not exist: '.$name);
	}
}
