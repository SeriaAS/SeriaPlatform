<?php

class TwitterAuthprovider extends SimplesamlAuthprovider
{
	public static function loadProviders()
	{
		$retv =& parent::loadProviders();
		SERIA_Authproviders::addProvider(new TwitterAuthprovider());
		return array_merge($retv, array(
			'configure' => array(
				'caption' => _t('Configure Twitter SSO'),
				'call' => array('TwitterAuthprovider', 'configureProvider'),
				'version' => 2
			)
		));
	}
	public function getParameters()
	{
		if (SERIA_AuthenticationState::available()) {
			$state = new SERIA_AuthenticationState();
			$stateId = $state->get('id');
		} else
			$stateId = 'null';
		return array(
			'authsources' => array(
				'twitter' => array(
					'authtwitter:Twitter',
					'key' => SERIA_Base::getParam('SimpleSAML_Twitter_key'),
					'secret' => SERIA_Base::getParam('SimpleSAML_Twitter_secret')
				),
			),
			'config' => array(
				'baseurlpath' => 'seria/components/SimplesamlAuthprovider/simplesamlphp.php/TwitterAuthprovider/'.$this->getProviderId().'/'.$stateId.'/'
			),
			'authsource' => 'twitter',
			'attributes' => array(
				'defaults' => array(
					'firstName' => '',
					'lastName' => '',
					'displayName' => ''
				),
				'load' => array(
					'unique' => array('screen_name', 0),
					'fullName' => array('name', 0),
					'displayName' => array('displayName', 0)
				),
				'combinations' => array(
				),
				'fillIn' => array(
					/*
					 * Fill in these fields into the SERIA_User object
					 */
					'unique',
					'firstName',
					'lastName',
					'displayName'
				)
			),
			'safeEmail' => false
		);
	}

	public function isAvailable()
	{
		return (SERIA_Base::getParam('SimpleSAML_Twitter_key') && SERIA_Base::getParam('SimpleSAML_Twitter_secret'));
	}

	/**/
	public function getProviderId()
	{
		return 'twitter_provider';
	}
	public function getName()
	{
		return _t('Twitter authentication');
	}

	public static function configureProvider($provider, $params=array())
	{
		if (!isset($params['redirect']))
			$params['redirect'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['cancel']))
			$params['cancel'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['submitCaption']))
			$params['submitCaption'] = _t('Save');
		if (isset($_POST['key']) && isset($_POST['secret'])) {
			SERIA_Base::setParam('SimpleSAML_Twitter_key', $_POST['key']);
			SERIA_Base::setParam('SimpleSAML_Twitter_secret', $_POST['secret']);
			SERIA_Base::redirectTo($params['redirect']);
			die();
		}
		$secret = SERIA_Base::getParam('SimpleSAML_Twitter_secret');
		$key = SERIA_Base::getParam('SimpleSAML_Twitter_key');
		?>
		<form method='post'>
			<input type='hidden' name='id' value="<?php echo htmlspecialchars($provider->getProviderId()); ?>" %XHTML_CLOSE_TAG%>
			<div>
				<h1 class='legend'><?php echo htmlspecialchars(_t('Configure Twitter SSO')); ?></h1>
				<p><?php echo htmlspecialchars(_t('Insert your key and secret below.')); ?></p>
			</div>
			<div>
				<table cellspacing='0' border='0'>
					<tr>
						<td><label for='api_key_elem'><?php echo htmlspecialchars('Key: '); ?></label></td>
						<td><input id='api_key_elem' type='text' name='key' value="<?php echo htmlspecialchars($key); ?>" style='width: 320px;' %XHTML_CLOSE_TAG%></td>
					</tr>
					<tr>
						<td><label for='secret_element'><?php echo htmlspecialchars('Secret: '); ?></label></td>
						<td><input id='secret_element' type='text' name='secret' value="<?php echo htmlspecialchars($secret); ?>" style='width: 320px;' %XHTML_CLOSE_TAG%></td>
					</tr>
				</table>
			</div>
			<div>
				<button type='submit'><?php echo $params['submitCaption']; ?></button>
				<button type='button' onclick="<?php echo htmlspecialchars('top.location.href = '.SERIA_Lib::toJSON($params['cancel']).';'); ?>"><?php echo htmlspecialchars(_t('Cancel')); ?></button>
			</div>
		</form>
		<?php
	}

	public function filterAttributes($attributes)
	{
		foreach ($attributes as $name => $value)
			SERIA_Base::debug($name.' => '.$value);
		$firstName = '';
		$lastName = '';
		if (isset($attributes['fullName'])) {
			$fullName = trim($attributes['fullName']);
			$pos = strrpos($fullName, ' ');
			if ($pos !== false) {
				$firstName = trim(substr($fullName, 0, $pos));
				$lastName = trim(substr($fullName, $pos + 1));
			} else
				$lastName = $fullName;
		}
		$attributes['firstName'] = $firstName;
		$attributes['lastName'] = $lastName;
		return $attributes;
	}
}
