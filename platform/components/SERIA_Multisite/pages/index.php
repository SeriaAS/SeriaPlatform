<?php
	SERIA_Base::pageRequires("admin");
?><s:gui title='{"Multisite Management"|_t}'>
	<h1 class="legend">{{"Multisite Management"|_t}}</h1>
	<p>{{"Manage your websites here"|_t}}</p>
<?php
	$sites = SERIA_Meta::all('SERIA_Site');
	$grid = new SERIA_MetaGrid($sites);
	echo $grid
		->addButton(_t("Add"), SERIA_Meta::manifestUrl('multisite', 'edit'))
		->rowClick(SERIA_Meta::manifestUrl('multisite', 'edit', array('id' => '%id%')))
		->output(array('title' => 200, 'domain'));
?>
</s:gui>
