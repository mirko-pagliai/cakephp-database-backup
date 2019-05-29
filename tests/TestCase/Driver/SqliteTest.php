<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright   Copyright (c) Mirko Pagliai
 * @link        https://github.com/mirko-pagliai/cakephp-database-backup
 * @license     https://opensource.org/licenses/mit-license.php MIT License
 */
namespace DatabaseBackup\Test\TestCase\Driver;

use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * SqliteTest class
 */
class SqliteTest extends DriverTestCase
{
    /**
     * @var \DatabaseBackup\Driver\Sqlite
     */
    protected $DriverClass = Sqlite::class;

    /**
     * Name of the database connection
     * @var string
     */
    protected $connection = 'test_sqlite';

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'plugin.DatabaseBackup.Sqlite/Articles',
        'plugin.DatabaseBackup.Sqlite/Comments',
    ];

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $expected = $this->getBinary('sqlite3') . ' ' . TMP . 'example.sq3 .dump';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $expected = $this->getBinary('sqlite3') . ' ' . TMP . 'example.sq3';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
    }

    /**
     * Test for `export()` method on failure
     * @test
     */
    public function testExportOnFailure()
    {
        $this->Driver = $this->getMockForDriver(['_exportExecutableWithCompression']);
        $this->Driver->method('_exportExecutableWithCompression')
            ->will($this->returnValue(sprintf(
                '%s %s .dump noExistingDir/dump.sql' . REDIRECT_TO_DEV_NULL,
                $this->getBinary('sqlite3'),
                $this->Driver->getConfig('database')
            )));
        parent::testExportOnFailure();
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $this->loadFixtures();

        parent::testImport();
    }

    /**
     * Test for `import()` method on failure
     * @test
     */
    public function testImportOnFailure()
    {
        $this->Driver = $this->getMockForDriver(['_importExecutableWithCompression', 'beforeImport']);
        $this->Driver->method('beforeImport')->will($this->returnValue(true));
        $this->Driver->method('_importExecutableWithCompression')->will($this->returnValue(sprintf(
            '%s %s .dump noExisting' . REDIRECT_TO_DEV_NULL,
            $this->getBinary('sqlite3'),
            $this->Driver->getConfig('database')
        )));
        parent::testImportOnFailure();
    }
}
