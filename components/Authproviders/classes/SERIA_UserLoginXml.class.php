<?php

class SERIA_UserLoginXml
{
	protected $sid;
	protected $user;

	public function __construct($sid, SERIA_User $user)
	{
		$this->sid = $sid;
		$this->user = $user;
		self::setUserXml($sid, $this->genUserXml());
	}

	protected static function getXmlFileDirectory()
	{
		return array((defined('SERIA_AUTHXML_DIRECTORY') ? SERIA_AUTHXML_DIRECTORY : SERIA_TMP_ROOT.'/authxml/'), 'authxml', '.txt');
	}
	protected static function getXmlFileDirectoryUrl()
	{
		return array((defined('SERIA_AUTHXML_DIRECTORY_URL') ? SERIA_AUTHXML_DIRECTORY_URL : SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/userxml.php?id='), '');
	}

	public static function getUserXmlFilename($sid)
	{
		$gen = self::getXmlFileDirectory();
		if (!file_exists(dirname($gen[0])))
			mkdir(dirname($gen[0]));
		if (!file_exists($gen[0]))
			mkdir($gen[0]);
		return $gen[0].$gen[1].$sid.$gen[2];
	}
	public static function getUserXmlUrl($sid)
	{
		$gen = self::getXmlFileDirectoryUrl();
		return $gen[0].$sid.$gen[1];
	}

	public static function setUserXml($sid, $data)
	{
		file_put_contents(self::getUserXmlFilename($sid), $data);
	}
	public static function deleteUserXml($sid)
	{
		if (file_exists(self::getUserXmlFilename($sid)))
			unlink(self::getUserXmlFilename($sid));
	}
	public static function getUserXml($sid)
	{
		return file_get_contents(self::getUserXmlFilename($sid));
	}
	public static function parseXml($xmlData)
	{
		$tree = ArrayXmlParser::parseToTree($xmlData);
		$users = array();
		foreach ($tree['children'] as $item) {
			if ($item['name'] == 'user' && $item['children'])
				$users[] = $item['children'];
		}
		if (count($users) != 1)
			throw new SERIA_Exception('Expected excactly one user object in XML! (Got '.count($users).')');
		$user = array_shift($users);
		$data = array();
		foreach ($user as $item) {
			switch ($item['name']) {
				case 'meta':
					if ($item['children']) {
						$metaValues = array();
						foreach ($item['children'] as $metaVal) {
							if (count($metaVal['values']) == 1)
								$metaValues[$metaVal['name']] = array_shift($metaVal['values']);
						}
						if (isset($metaValues['name']) && isset($metaValues['value'])) {
							if (!isset($data['meta']))
								$data['meta'] = array();
							$data['meta'][$metaValues['name']] = $metaValues['value'];
						}
					}
					break;
				default:
					if (isset($item['values']) && count($item['values']) == 1)
						$data[$item['name']] = array_shift($item['values']);
			}
		}
		return $data;
	}
	public static function getParsedXml($sid)
	{
		$xmlData = self::getUserXml($sid);
		if ($xmlData)
			return self::parseXml($xmlData);
		else
			return false;
	}

	public static function getAllSids()
	{
		$gen = self::getXmlFileDirectory();
		if (file_exists($gen[0]) && is_dir($gen[0])) {
			$dh = opendir($gen[0]);
			if (!$dh)
				return array();
			$sids = array();
			$len = strlen($gen[1]);
			$plen = strlen($gen[2]);
			while (($filename = readdir($dh)) !== false) {
				$prefix = substr($filename, 0, $len);
				if ($prefix != $gen[1])
					continue;
				$sid = substr($filename, $len);
				$postfix = substr($sid, -$plen);
				if ($postfix != $gen[2])
					continue;
				$sid = substr($sid, 0, -$plen);
				$sids[] = $sid;
			}
			closedir($dh);
			return $sids;
		}
		return array();
	}
	public static function cleanupSids()
	{
		SERIA_Base::debug('Cleaning up SID-XML-files..');
		$sids = self::getAllSids();
		foreach ($sids as $sid) {
			$sess = new SeriaPlatformSession($sid);
			try {
				if ($sess->getUser() === false) {
					/*
					 * Logged out on this session
					 */
					SERIA_Base::debug('Removing roam XML for session '.$sid);
					self::deleteUserXml($sid);
					continue;
				}
			} catch (SERIA_NotFoundException $e) {
				/* User not found! */
				SERIA_Base::debug('ERROR: USER NOT FOUND: Removing roam XML for session '.$sid);
				self::deleteUserXml($sid);
				continue;
			}
			$data = self::getParsedXml($sid);
			if ($data && isset($data['uid']) && $data['uid'] == $sess->getUser()->get('id')) {
				SERIA_Base::debug('Login status for '.$sid.' is ok ('.$sess->getUser()->get('display_name').')');
				continue;
			}
			SERIA_Base::debug('Login for '.$sid.' has changed uid. Removing XML..');
			self::deleteUserXml($sid);
		}
	}

	protected function genUserXml()
	{
		ob_start();
		$hostname = parse_url(SERIA_HTTP_ROOT, PHP_URL_HOST);
		echo "<?xml version=\"1.0\"?>\n";
		?>
			<user>
				<hostname><?php echo htmlspecialchars($hostname); ?></hostname>
				<uid><?php echo $this->user->get('id'); ?></uid>
				<email><?php echo htmlspecialchars($this->user->get('email')); ?></email>
				<username><?php echo htmlspecialchars($this->user->get('username')); ?></username>
				<firstName><?php echo htmlspecialchars($this->user->get('firstName')); ?></firstName>
				<lastName><?php echo htmlspecialchars($this->user->get('lastName')); ?></lastName>
				<displayName><?php echo htmlspecialchars($this->user->get('displayName')); ?></displayName>
				<loginUrl><?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/platform/pages/login.php'); ?></loginUrl>
				<profileEditUrl><?php echo htmlspecialchars(SERIA_HTTP_ROOT); ?></profileEditUrl>
				<logoutUrl><?php echo htmlspecialchars(SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/externalLogout.php'); ?></logoutUrl>
				<timestamp><?php echo time(); ?></timestamp>
				<?php
					$meta = $this->user->getAllMetaExtended();
					foreach ($meta as $values) {
						?>
						<meta>
							<?php
								foreach ($values as $name => $value)
									echo '<'.$name.'>'.htmlspecialchars($value).'</'.$name.'>';
							?>
						</meta>
						<?php
					}
				?>
			</user>
		<?php
		return ob_get_clean();
	}
}
