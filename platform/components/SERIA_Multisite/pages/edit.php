<s:gui title='{"Edit site"|_t}'>
	<h1 class="legend">{{"Edit site"|_t}}</h1>
<?php
	if(isset($_GET['id']))
	{
		$site = SERIA_Meta::load('SERIA_Site', $_GET['id']);
	}
	else
	{
		$site = new SERIA_Site();
	}
	$form = $site->editAction();
	if($form->success)
	{
		SERIA_Base::redirectTo(SERIA_Meta::manifestUrl('multisite'));
	}

	$delete = $site->deleteAction();
	if($delete->success)
	{
		SERIA_Base::redirectTo(SERIA_Meta::manifestUrl('multisite'));
	}

	if(!SERIA_Meta::isNew($site))
	{
		$aliasRS = $site->getSiteAliases();
		$aliases = $aliasRS->grid()
			->addButton(_t("Add site alias"), SERIA_Meta::manifestUrl('multisite', 'alias/edit', array('siteId' => $site->get("id"))))
			->rowClick(SERIA_Meta::manifestUrl('multisite','alias/edit', array('siteId' => $site->get("id"), 'id' => '%id%'))->__toString())
			->output(array('domain', 'domainType'));
	}
	else
		$aliases = "";


	echo $form->begin()."<table><tbody>
		<tr><th>".$form->label('domain')."</th><td>".$form->field("domain")."</td><td rowspan='7'>".$aliases."</td></tr>
		<tr><th>".$form->label('title')."</th><td>".$form->field("title")."</td></tr>
		<tr><th>".$form->label('notes')."</th><td>".$form->field("notes")."</td></tr>
		<tr><th>".$form->label('dbName')."</th><td>".$form->field("dbName")."</td></tr>
		<tr><th>".$form->label('timezone')."</th><td>".$form->field("timezone")."</td></tr>
		<tr><th>".$form->label('currency')."</th><td>".$form->field("currency")."</td></tr>
		<tr><th>".$form->label('errorMail')."</th><td>".$form->field("errorMail")."</td></tr>
		<tr><td colspan='2'>".$form->field("is_published")." ".$form->label('is_published')."</td></tr>
	</tbody><tfoot><td colspan='3'>".$form->submit(_t("Save"))." ".(SERIA_Meta::isNew($site)?'':"<a href='".SERIA_Meta::manifestUrl('multisite', 'alias/edit', array('siteId' => $site->get("id")))."'>"._t("Add alias")."</a> <a href='".$delete."'>"._t("Delete site")."</a>")."
 <a href='".SERIA_Meta::manifestUrl('multisite')."'>"._t("Go back")."</a></td></tfoot></table>".$form->end();

?></s:gui>
