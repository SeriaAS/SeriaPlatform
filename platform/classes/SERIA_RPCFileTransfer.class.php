<?php

class SERIA_RPCFileTransfer implements SERIA_RPCServer
{
	protected $rpcClient;

	public function __construct($serviceName)
	{
		$this->rpcClient = SERIA_RPCClient::connect($serviceName, 'SERIA_RPCFileTransfer');
	}

	public function getHostname()
	{
		return $this->rpcClient->getHostname();
	}
	public function getBaseUrl()
	{
		return $this->rpcClient->getBaseUrl();
	}

	public function uploadFile(SERIA_File $file)
	{
		$localPath = $file->get('localPath');
		if (!$localPath)
			throw new SERIA_Exception(_t('Not a local file.'));
		$sha1 = sha1_file($localPath);
		$this->rpcClient->hello(); /* Get auth before sending file */
		$results = $this->rpcClient->callWithFileAttachments('uploadFile', array(), array('file' => $file));
		if ($results && is_array($results)) {
			if (!isset($results['file']))
				throw new SERIA_Exception(_t('No results on our file.'));
			$results = $results['file'];
			if ($results['error'])
				throw new SERIA_Exception(_t('File upload failed: %ERR%', array('ERR' => $results['error'])));
			if ($sha1 != $results['sha1'])
				throw new SERIA_Exception(_t('Upload failed. (sha1 does not match)'));
			return $results['id'];
		} else
			throw new SERIA_Exception(_t('File uploader should return results as an array.'));
	}
	public function downloadFile($fileid)
	{
		$results = $this->rpcClient->downloadFile($fileid);
		if ($results && is_array($results)) {
			if (sha1($results['contents']) != $results['sha1'])
				throw new SERIA_Exception(_t('Sha1 of downloaded file does not match.'));
			$fbin = tempnam(sys_get_temp_dir(), 'downloader');
			try {
				file_put_contents($fbin, $results['contents']);
				unset($results['contents']);
				$file = new SERIA_File();
				$file->populateObjectFromFilePath($fbin, $results['filename'], false, 'rename');
				return $file;
			} catch (Exception $e) {
				if (file_exists($fbin))
					unlink($fbin);
				throw $e;
			}
		} else
			throw new SERIA_Exception(_t('File downloader should return results as an array.'));
	}
	public function get($fileid, $param)
	{
		return $this->rpcClient->get($fileid, $param);
	}
	public function set($fileid, $param, $value)
	{
		$this->rpcClient->set($fileid, $param, $value);
	}
	public function getMeta($fileid, $param)
	{
		return $this->rpcClient->getMeta($fileid, $param);
	}
	public function setMeta($fileid, $param, $value)
	{
		$this->rpcClient->setMeta($fileid, $param, $value);
	}
	public function getRelatedFiles($fileid, $filter=null)
	{
		return $this->rpcClient->getRelatedFiles($fileid, $filter);
	}

	public static function rpc_hello()
	{
		SERIA_RPCHost::requireAuthentication();
		return true;
	}
	public static function rpc_uploadFile()
	{
		/*
		 * File supplied as multipart/form-data.
		 * Returns file id.
		 */
		SERIA_RPCHost::requireAuthentication();
		$results = array();
		foreach ($_FILES as $nam => $fileinfo) {
			switch ($fileinfo['error']) {
				case UPLOAD_ERR_OK:
					$error = false;
					$file = new SERIA_File($fileinfo['tmp_name'], $fileinfo['name']);
					$sha1 = sha1_file($file->get('localPath'));
					break;
				case UPLOAD_ERR_INI_SIZE:
					$error = _t('Upload was denied because of size. (php.ini)');
					break;
				case UPLOAD_ERR_FORM_SIZE:
					$error = _t('Upload was denied because of size. (form specified max)');
					break;
				case UPLOAD_ERR_PARTIAL:
					$error = _t('Upload was truncated.');
					break;
				case UPLOAD_ERR_NO_FILE:
					$error = _t('No file received.');
					break;
				case UPLOAD_ERR_NO_TMP_DIR:
					$error = _t('Can\'t find a tmp directory.');
					break;
				case UPLOAD_ERR_CANT_WRITE:
					$error = _t('Can\'t write files to tmp.');
					break;
				case UPLOAD_ERR_EXTENSION:
					$error = _t('An extension has denied upload.');
					break;
				default:
					$error = _t('Unknown upload error.');
					break;
			}
			$results[$nam] = array(
				'id' => ($error === false ? $file->get('id') : false),
				'error' => $error,
				'sha1' => ($error === false ? $sha1 : false)
			);
		}
		return $results;
	}
	public static function rpc_downloadFile($fileid)
	{
		/*
		 * Return file contents
		 */
		SERIA_RPCHost::requireAuthentication();
		$file = SERIA_File::createObject($fileid);
		if (!$file)
			throw new SERIA_Exception(_t('File not found.'));
		$localPath = $file->get('localPath');
		if (!$localPath)
			throw new SERIA_Exception(_t('File is not local.'));
		$contents = file_get_contents($localPath);
		return array(
			'contents' => &$contents,
			'sha1' => sha1($contents),
			'filename' => $file->get('filename')
		);
	}
	public static function rpc_get($fileid, $param)
	{
		SERIA_RPCHost::requireAuthentication();
		$file = SERIA_File::createObject($fileid);
		if (!$file)
			throw new SERIA_Exception(_t('File not found.'));
		return $file->get($param);
	}
	public static function rpc_set($fileid, $param, $value)
	{
		SERIA_RPCHost::requireAuthentication();
		$file = SERIA_File::createObject($fileid);
		if (!$file)
			throw new SERIA_Exception(_t('File not found.'));
		return $file->set($param, $value);
	}
	public static function rpc_getMeta($fileid, $param)
	{
		SERIA_RPCHost::requireAuthentication();
		$file = SERIA_File::createObject($fileid);
		if (!$file)
			throw new SERIA_Exception(_t('File not found.'));
		return $file->getMeta($param);
	}
	public static function rpc_setMeta($fileid, $param, $value)
	{
		SERIA_RPCHost::requireAuthentication();
		$file = SERIA_File::createObject($fileid);
		if (!$file)
			throw new SERIA_Exception(_t('File not found.'));
		return $file->setMeta($param, $value);
	}
	public static function rpc_getRelatedFiles($fileid, $filter)
	{
		SERIA_RPCHost::requireAuthentication();
		$file = SERIA_File::createObject($fileid);
		if (!$file)
			throw new SERIA_Exception(_t('File not found.'));
		$files = $file->getRelatedFiles($filter);
		$ids = array();
		foreach ($files as $rel)
			$ids[] = $rel->get('id');
		return $ids;
	}
}
