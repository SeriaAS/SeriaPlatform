<?php

class WindowsLiveXmlConfig
{
	protected $provider;

	public function __construct($provider)
	{
		$this->provider = $provider;
	}

	public function setDirty($dirty=true)
	{
		if ($dirty) {
			if (!SERIA_Base::getParam($this->provider->getProviderId().'.dirty'))
				SERIA_Base::setParam($this->provider->getProviderId().'.dirty', true);
		} else {
			if (SERIA_Base::getParam($this->provider->getProviderId().'.dirty'))
				SERIA_Base::setParam($this->provider->getProviderId().'.dirty', false);
		}
	}
	public function isDirty()
	{
		return SERIA_Base::getParam($this->provider->getProviderId().'.dirty');
	}

	public function getXmlText()
	{
		ob_start();
?><windowslivelogin>
	<appid><?php echo htmlspecialchars($this->provider->getApplicationId()); ?></appid>
	<secret><?php echo htmlspecialchars($this->provider->getSecret()); ?></secret>
	<securityalgorithm>wsignin1.0</securityalgorithm>
	<returnurl><?php echo SERIA_HTTP_ROOT.'/seria/components/WindowsLiveAuthprovider/pages/handler.php'; ?></returnurl>
	<policyurl><?php echo SERIA_HTTP_ROOT; ?></policyurl>
</windowslivelogin>
<?php
		return ob_get_clean();
	}
	public function generateXml($filename)
	{
		file_put_contents($filename, $this->getXmlText());
	}

	public function getXmlConfigFilename()
	{
		$component = SERIA_Components::getComponent('windows_live_authprovider_component');
		if (!file_exists($component->getPrivateCodegenDir()))
			mkdir($component->getPrivateCodegenDir(), 0755);
		return $component->getPrivateCodegenDir().'/'.$this->provider->getProviderId().'.xml';
	}
	public function getXmlConfig()
	{
		$filename = $this->getXmlConfigFilename();
		if (!file_exists($filename) || $this->isDirty()) {
			$this->generateXml($filename);
			$this->setDirty(false);
		}
		return $filename;
	}
}