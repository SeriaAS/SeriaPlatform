<s:gui title="{'User management is not handled by this site'|_t|htmlspecialchars}">
	<h1 class='legend'>{{'User management is not handled by this site'|_t|htmlspecialchars}}</h1>
	<?php
		$this->gui->activeMenuItem('controlpanel/users');
		$external = SERIA_AuthprovidersConfiguration2::usingExternalAuthentication();
		if (!$external) {
			$menuitem = $this->gui->getMenuItem('controlpanel/users');
			SERIA_Base::redirectTo($menuitem['url']);
			die();
		}
		$hostname = $external->getHostname();
		$url = 'http://'.$hostname.'/?route=components/authproviders/usermanagement';
		ob_start();
		?><a href="{{$url|htmlspecialchars}}">{{$url}}</a><?php
		$link = ob_get_clean();
	?>
	<p>{{'User management is handled by the server at %0%. To manage user accounts, go to %1%.'|_t($hostname, $link)}}</p>
</s:gui>