<?php

use CMSMS\AppParams;
use CMSMS\AppSingle;

require_once('class.CmsApp.pseudo.php');
require_once(CMSMS.'/lib/classes/class.AppParams.php');

class Test_cms_siteprefs extends UnitTestCase
{
  public function setUp()
  {
    parent::setUp();

    $config = cmsms()->GetConfig();
	$db = cmsms()->GetDb();
    $dbdict = $db->NewDataDictionary();
    $taboptarray = array('mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci');

    $flds = '
	sitepref_name C(255) KEY,
	sitepref_value text,
	create_date DT,
	modified_date DT
    ';
    $sqlarray = $dbdict->CreateTableSQL($config['db_prefix'].'siteprefs', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
  }

  public function tearDown()
  {
    $config = AppSingle::Config();
	$db = AppSingle::Db();
    $dbdict = $db->NewDataDictionary();
    $sqlarray = $dbdict->DropTableSQL($config['db_prefix'].'siteprefs');
    $return = $dbdict->ExecuteSQLArray($sqlarray);
  }

  public function TestSetGet1()
  {
    AppParams::set('test1','val1');
    AppParams::set('test2','val2');
    $this->assertEqual(AppParams::get('test1'),'val1');
  }

  public function TestExists()
  {
    $this->assertTrue(AppParams::exists('test1'));
  }

  public function TestRemove()
  {
    AppParams::remove('test2');
    $this->assertFalse(AppParams::exists('test2'));
  }

  public function TestExists2()
  {
    AppParams::set('test1','');
    $this->assertTrue(AppParams::exists('test1'));

    AppParams::set('test1',null);
    $this->assertTrue(AppParams::exists('test1'));
  }
} // class
