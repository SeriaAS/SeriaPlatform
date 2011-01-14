<?php
class SERIA_SiteAlias extends SERIA_MetaObject
{
	public static function Meta($instance=NULL) {
		return array(
			'table' => '{sites_aliases}',
			'fields' => array(
				'siteId' => array('SERIA_Site required', _t("Site")),
				'domain' => array('hostname required', _t("Domain")),
				'domainType' => array('enum required', _t("Type"), array(
					'type' => 'enum("forwarder","alias")',
					'values' => array('forwarder' => _t("Forwarder"), 'alias' => _t("Alias")),
				)),
			),
		);
	}

	public function editAction()
	{
		return SERIA_Meta::editAction('editSiteAlias', $this, array('domain', 'domainType'));
	}

	public function deleteAction()
	{
		return SERIA_Meta::deleteAction('deleteSiteAlias', $this);
	}
}
