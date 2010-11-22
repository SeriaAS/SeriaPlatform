<?php

/**
 * Simple redirect urls, to hide get-params (shorter URL?).
 *
 * @author Jan-Espen Pettersen
 *
 */
class sspmod_simpleurl_UrlGenerator
{
	const ID = 'sspmod_authfacebook_UrlGenerator.id';

	public static function getRedirectId(&$state, $path, $params=array())
	{
		$id = sha1(mt_rand().mt_rand());
		$id = substr($id, 0, 16); /* Fixed length */
		if (!isset($state[self::ID]))
			$state[self::ID] = array();
		$root = $_SERVER['DOCUMENT_ROOT'];
		if (DIRECTORY_SEPARATOR != '/')
			$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		while ($path && $path[0] == '/')
			$path = substr($path, 1);
		$pathcomp = explode('/', $path);
		$trav = array();
		$filename = false;
		while (($part = array_shift($pathcomp))) {
			$trav[] = $part;
			$filename = $root.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $trav);
			if (!file_exists($filename))
				throw new Exception('File not found.');
			$ext = pathinfo($filename, PATHINFO_EXTENSION);
			if (!is_dir($filename) && $ext == 'php')
				break;
			$filename = false;
		}
		if ($filename === false) {
			die($root.DIRECTORY_SEPARATOR.implode('/', $pathcomp));
			throw new Exception('No filepath to run.');
		}
		$path = '/'.implode('/', $trav);
		$subPath = implode('/', $pathcomp);
		$sysPath = DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $trav);
		$sysSubPath = implode(DIRECTORY_SEPARATOR, $pathcomp);
		$state[self::ID][$id] = array(
			$filename,
			$path,
			$subPath,
			$sysPath,
			$sysSubPath,
			$params
		);
		SimpleSAML_Auth_State::saveState($state, $state[SimpleSAML_Auth_State::STAGE]);
		return $id;
	}
	public static function runPageView(&$state, $id=false)
	{
		if ($id === false) {
			if (isset($_GET['id'])) {
				$id = $_GET['id'];
				unset($_GET['id']);
			} else
				throw new Exception('Invalid redirect url!');
		}
		if (!isset($state[self::ID]) || !isset($state[self::ID][$id]))
			throw new Exception('No state info for this url');
		$root = $_SERVER['DOCUMENT_ROOT'];
		SimpleSAML_Auth_State::saveState($state, $state[SimpleSAML_Auth_State::STAGE]);
		$vector = $state[self::ID][$id];
		unset($state[self::ID][$id]);
		$_SERVER['SCRIPT_FILENAME'] = $vector[0];
		$_SERVER['SCRIPT_NAME'] = $vector[1];
		if ($vector[2]) {
			$_SERVER['PATH_INFO'] = '/'.$vector[2];
			$_SERVER['PATH_TRANSLATED'] = $root.DIRECTORY_SEPARATOR.$vector[4];
		} else {
			if (isset($_SERVER['PATH_INFO']))
				unset($_SERVER['PATH_INFO']);
			if (isset($_SERVER['PATH_TRANSLATED']))
				unset($_SERVER['PATH_TRANSLATED']);
		}
		$getParams = array_merge($vector[5], $_GET);
		$postParams = $_POST;
		$cookies = $_COOKIE;
		$req = array_merge($getParams, $postParams, $cookies);
		$_SERVER['QUERY_STRING'] = http_build_query($getParams);
		$reqUri = $vector[1];
		if ($vector[2])
			$reqUri .= '/'.$vector[2];
		if ($getParams)
			$reqUri .= '?'.http_build_query($getParams);
		$_SERVER['REQUEST_URI'] = $reqUri;
		$selfPath = $vector[1];
		if ($vector[2])
			$selfPath .= '/'.$vector[2];
		$_SERVER['PHP_SELF'] = $selfPath;
		$_GET = $getParams;
		$_POST = $postParams;
		$_COOKIE = $cookies;
		$_REQUEST = $req;
		SERIA_Base::debug('Running '.$vector[0].' with query '.$_SERVER['QUERY_STRING'].'.');

		$simplesaml_session = SimpleSAML_Session::getInstance();
		$simplesaml_session->saveSession();

		require($vector[0]);
	}
	public static function getRedirectUrl(&$state, $url)
	{
		$urlcomp = parse_url($url);
		if (isset($urlcomp['query']) && $urlcomp['query'])
			parse_str($urlcomp['query'], $params);
		else
			$params = array();
		$id = self::getRedirectId($state, $urlcomp['path'], $params);
		$StateID = SimpleSAML_Auth_State::saveState($state, $state[SimpleSAML_Auth_State::STAGE]);
		$base = Simplesaml_Utilities::getBaseURL();
		$len = strlen($base);
		if ($len == 0 || $base[$len - 1] != '/')
			$base .= '/';
		return $base.'/module.php/simpleurl/redirect.php?state='.urlencode($StateID).'&id='.urlencode($id);
	}
}
