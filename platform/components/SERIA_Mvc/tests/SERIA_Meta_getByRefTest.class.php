<?php

require_once(dirname(__FILE__).'/../../../../main.php');
require_once 'PHPUnit/Framework.php';

class TestSeriaMetaClass extends SERIA_MetaObject
{
	protected static $databaseTableName = '{test_table_seria_meta_auto_delete}';

	public static function deleteFromDatabase()
	{
		try {
			SERIA_Base::db()->exec('DROP TABLE '.self::$databaseTableName);
		} catch (Exception $e) {
		}
	}
	public static function Meta($instance = null)
	{
		return array(
			'table' => self::$databaseTableName,
			'primaryKey' => 'mypk',
			'fields' => array(
				'mypk' => array('integer required', 'Primary key'),
				'metaObject' => array('SERIA_MetaObject', 'Object ref')
			)
		);
	}
}

class SERIA_Meta_getbyRefTest extends PHPUnit_Framework_TestCase
{
	public function testGetByRef()
	{
		TestSeriaMetaClass::deleteFromDatabase();
		SERIA_Meta::_syncColumnSpec(SERIA_Meta::_getSpec(TestSeriaMetaClass));
		$first = new TestSeriaMetaClass();
		$first->set('mypk', 1);
		SERIA_Meta::save($first);
		$first = SERIA_Meta::load('TestSeriaMetaClass', 1);
		$second = new TestSeriaMetaClass();
		$second->set('mypk', 2);
		$second->set('metaObject', $first);
		SERIA_Meta::save($second);
		$this->assertTrue(is_object($second->get('metaObject')));
		$this->assertEquals('TestSeriaMetaClass', get_class($second->get('metaObject')));
		$this->assertEquals(1, $second->get('metaObject')->get('mypk'));
		$second = SERIA_Meta::load('TestSeriaMetaClass', 2);
		$this->assertTrue(is_object($second->get('metaObject')));
		$this->assertEquals('TestSeriaMetaClass', get_class($second->get('metaObject')));
		$this->assertEquals(1, $second->get('metaObject')->get('mypk'));
		TestSeriaMetaClass::deleteFromDatabase();
	}
}