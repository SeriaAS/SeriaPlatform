<?php
class SERIA_Site extends SERIA_MetaObject {
	public static function Meta($instance=NULL) {
		return array(
			'table' => '{sites}',
			'fields' => array(
				'domain' => array('domain required', _t("Domain")),
				'title' => array('title required', _t("Title")),
				'created_date' => 'createdDate',
				'created_by' => 'createdBy',
				'modified_date' => 'modifiedDate',
				'modified_by' => 'modifiedBy',
				'is_published' => array('boolean', _t("Is published")),
				'notes' => array('text', _t("Notes")),
				'dbName' => array('safestring required', _t("Database name")),
				'timezone' => array('timezone', _t("Timezone")),
				'currency' => array('currencycode', _t("Currency")),
				'errorMail' => array('email', _t("Error e-mail")),
				'maintainDate' => array('datetime', _t("Last maintain run")),
			),
		);
	}

	public function MetaBeforeSave()
	{
		return SERIA_Base::isAdministrator();
	}

	public function getSiteAliases()
	{
		$aliases = SERIA_Meta::all('SERIA_SiteAlias')->where('siteId=:id', $this);
		return $aliases;
	}

	public function deleteAction()
	{
		return SERIA_Meta::deleteAction('siteDelete', $this);
	}

	public function editAction()
	{
		return SERIA_Meta::editAction('siteEdit', $this, array('domain','title','is_published','notes','dbName','timezone','currency','errorMail'));
	}
}
/*
+--------------+--------------+------+-----+--------------------+-------+
| Field        | Type         | Null | Key | Default            | Extra |
+--------------+--------------+------+-----+--------------------+-------+
| id           | int(11)      | NO   | PRI | NULL               |       |
| domain       | varchar(100) | YES  | UNI | NULL               |       |
| title        | varchar(100) | YES  |     | NULL               |       |
| created_date | datetime     | YES  |     | NULL               |       |
| created_by   | int(11)      | YES  |     | NULL               |       |
| is_published | tinyint(1)   | YES  |     | NULL               |       |
| notes        | text         | YES  |     | NULL               |       |
| dbName       | varchar(100) | YES  |     | NULL               |       |
| timezone     | varchar(100) | YES  |     | Europe/Oslo        |       |
| currency     | varchar(100) | YES  |     | EUR                |       |
| errorMail    | varchar(100) | YES  |     | errors@example.com |       |
+--------------+--------------+------+-----+--------------------+-------+
*/
