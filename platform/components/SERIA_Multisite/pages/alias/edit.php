<s:gui title='{"Edit site alias"|_t}'>
	<h1 class="legend">{{"Edit site alias"|_t}}</h1>
<?php
	if(isset($_GET['siteId']))
	{
		$site = SERIA_Meta::load('SERIA_Site', $_GET['siteId']);
	}
	else
	{
		SERIA_Base::redirectTo($this->Meta['urls']['multisite']);
	}

	if(isset($_GET['id']))
	{
		$siteAlias = SERIA_Meta::load('SERIA_SiteAlias', $_GET['id']);
		if($siteAlias->get("siteId") != $site)
			SERIA_Base::redirectTo($this->Meta['urls']['multisite']);
	}
	else
	{
		$siteAlias = new SERIA_SiteAlias();
		$siteAlias->set("siteId", $site->get("id"));
	}

	$form = $siteAlias->editAction();
	if($form->success)
	{
		SERIA_Base::redirectTo($this->Meta['urls']['multisite']['edit'].'&id='.$site->get("id"));
	}
	$delete = $siteAlias->deleteAction();
	if($delete->success)
	{
		SERIA_Base::redirectTo($this->Meta['urls']['multisite']['edit'].'&id='.$site->get("id"));
	}
	echo $form->begin()."<table><tbody>
		<tr><th>".$form->label('domain')."</th><td>".$form->field("domain")."</td></tr>
		<tr><th>".$form->label('domainType')."</th><td>".$form->field("domainType")."</td></tr>
	</tbody><tfoot><td colspan='2'>".$form->submit(_t("Save"))." <a href='".$delete."' onclick='return confirm(\""._t("Are you sure?")."\");'>"._t("Delete alias")."</a> <a href='".$this->Meta['urls']['multisite']['edit'].'&id='.$site->get("id")."'>"._t("Abort")."</a></td></tfoot></table>".$form->end();

?></s:gui>
