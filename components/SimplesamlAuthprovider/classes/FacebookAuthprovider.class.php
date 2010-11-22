<?php

class FacebookAuthprovider extends SimplesamlAuthprovider
{
	public static function loadProviders()
	{
		$retv =& parent::loadProviders();
		SERIA_Authproviders::addProvider(new FacebookAuthprovider());
		return array_merge($retv, array(
			'configure' => array(
				'caption' => _t('Configure Facebook SSO'),
				'call' => array('FacebookAuthprovider', 'configureProvider'),
				'version' => 2
			)
		));
	}

	public function getParameters()
	{
		return array(
			'authsources' => array(
				'facebook' => array(
					'authfacebook:Facebook',
					'api_key' => SERIA_Base::getParam('SimpleSAML_Facebook_api_key'),
					'secret' => SERIA_Base::getParam('SimpleSAML_Facebook_secret'),
					'ext_params' => array('email')
				),
			),
			'config' => array(
				'baseurlpath' => 'seria/components/SimplesamlAuthprovider/simplesamlphp.php/FacebookAuthprovider/'.$this->getProviderId().'/'
			),
			'authsource' => 'facebook',
			'attributes' => array(
				'defaults' => array(
					'email' => '',
					'firstName' => '',
					'lastName' => ''
				),
				'load' => array(
					'unique' => array('uid', 0),
					'firstName' => array('givenName', 0),
					'lastName' => array('sn', 0),
					'displayName' => array('cn', 0),
					'email' => array('email', 0)
				),
				'combinations' => array(
				),
				'fillIn' => array(
					/*
					 * Fill in these fields into the SERIA_User object
					 */
					'unique',
					'email',
					'firstName',
					'lastName',
					'displayName'
				)
			),
			'safeEmail' => true
		);
	}

	public function isAvailable()
	{
		return (SERIA_Base::getParam('SimpleSAML_Facebook_api_key') && SERIA_Base::getParam('SimpleSAML_Facebook_secret'));
	}

	/**/
	public function getProviderId()
	{
		return 'facebook_provider';
	}
	public function getName()
	{
		return _t('Facebook authentication');
	}

	public function configureProvider($provider, $params=array())
	{
		if (!isset($params['redirect']))
			$params['redirect'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['cancel']))
			$params['cancel'] = SERIA_HTTP_ROOT.'/seria/components/Authproviders/pages/providers.php';
		if (!isset($params['submitCaption']))
			$params['submitCaption'] = _t('Save');
		if (isset($_POST['api_key']) && isset($_POST['secret'])) {
			SERIA_Base::setParam('SimpleSAML_Facebook_api_key', $_POST['api_key']);
			SERIA_Base::setParam('SimpleSAML_Facebook_secret', $_POST['secret']);
			SERIA_Base::redirectTo($params['redirect']);
			die();
		}
		$secret = SERIA_Base::getParam('SimpleSAML_Facebook_secret');
		$api_key = SERIA_Base::getParam('SimpleSAML_Facebook_api_key');
		?>
		<form method='post'>
			<input type='hidden' name='id' value="<?php echo htmlspecialchars($provider->getProviderId()); ?>" %XHTML_CLOSE_TAG%>
			<div>
				<h1 class='legend'><?php echo htmlspecialchars(_t('Configure Facebook SSO')); ?></h1>
				<p><?php echo htmlspecialchars(_t('Insert your API-key and secret below.')); ?></p>
			</div>
			<div>
				<table cellspacing='0' border='0'>
					<tr>
						<td><label for='api_key_elem'><?php echo htmlspecialchars('API-key: '); ?></label></td>
						<td><input id='api_key_elem' type='text' name='api_key' value="<?php echo htmlspecialchars($api_key); ?>" style='width: 320px;' %XHTML_CLOSE_TAG%></td>
					</tr>
					<tr>
						<td><label for='secret_element'><?php echo htmlspecialchars('Secret key: '); ?></label></td>
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
}