<?php

SERIA_Template::cssInclude(SERIA_Filesystem::getCachedUrlFromPath(dirname(__FILE__).'/admin.css'));

if(!$enabled) {

	echo "<h1 class='legend'>".$app->getName()."</h1>
<p>"._t("Authentication providers have not been enabled. To enable define SERIA_AUTHPROVIDERS_ENABLED = 1 in the site configuration.")."</p>";

}
else
{
?><h1 class='legend'><?php echo htmlspecialchars($app->getName()); ?></h1>
<fieldset id='authproviders_settings'>
	<legend><?php echo htmlspecialchars(_t('Settings')); ?></legend>
	<form method='post'>
		<div class='aps aps_top aps_bottom'>
			<p>
				<input onclick='publishToggleSwitch(this);' onchange='publishToggleSwitch(this);' type='checkbox' id='c_publish_enabled' name='publish_enabled' value='1'<?php if ($published) echo ' checked=\'checked\''; ?> %XHTML_CLOSE_TAG%>
				<label for='c_publish_enabled'><?php echo htmlspecialchars(_t('Notify other servers below this domain about users that have authenticated on this website.')); ?></label>
			</p>
			<p>
				<label for='c_publish_domain'><?php echo htmlspecialchars(_t('Top level domain: '))?></label>
<?php
	$pi = new SERIA_Url(SERIA_HTTP_ROOT);
	$host = $pi->getHost();
	$hostParts = explode(".", $host);
	while(sizeof($hostParts)>2)
		array_shift($hostParts);
	$host = ".".implode(".", $hostParts);
?>
				<input type='text' id='c_publish_domain' name='publish_domain' value="<?php echo ($published ? htmlspecialchars($published) : $host); ?>"<?php if (!$published) echo ' disabled=\'disabled\''; ?> %XHTML_CLOSE_TAG%>
			</p>
			<script type='text/javascript'>
				<!--
					var publishToggleSwitch = function () {
						var publishDomainObj = document.getElementById('c_publish_domain');
						return function (object) {
							if (object.checked)
								publishDomainObj.disabled = false;
							else
								publishDomainObj.disabled = 'disabled'; 
						}
					}
					publishToggleSwitch = publishToggleSwitch();
				-->
			</script>
		</div>
		<div class='aps aps_bottom'>
			<button type='submit' name='submitbutton' value='0'><?php echo htmlspecialchars(_t('Save settings')); ?></button>
		</div>
	</form>
</fieldset>
<?php
}
?>
