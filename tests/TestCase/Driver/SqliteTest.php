<?php
/**
 * This file is part of cakephp-mysql-backup.
 *
 * cakephp-mysql-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-mysql-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-mysql-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace MysqlBackup\Test\TestCase\Driver;

use Cake\Core\Configure;
use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Sqlite;
use MysqlBackup\TestSuite\DriverTestCase;
use Reflection\ReflectionTrait;

/**
 * SqliteTest class
 */
class SqliteTest extends DriverTestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \MysqlBackup\Driver\Sqlite
     */
    protected $Sqlite;

    /**
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'plugin.mysql_backup.sqlite\Articles',
        'plugin.mysql_backup.sqlite\Comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        Configure::write(MYSQL_BACKUP . '.connection', 'test_sqlite');

        parent::setUp();

        $this->Sqlite = new Sqlite($this->getConnection());
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        Configure::write(MYSQL_BACKUP . '.connection', 'test');

        unset($this->Sqlite);
    }

    /**
     * Test for `getCompression()` method
     * @test
     */
    public function testGetCompression()
    {
        $compressions = [
            'backup.sql' => false,
            'backup.sql.bz2' => 'bzip2',
            'backup.sql.gz' => 'gzip',
            'text.txt' => null,
        ];

        foreach ($compressions as $filename => $expectedCompression) {
            $this->assertEquals($expectedCompression, $this->Sqlite->getCompression($filename));
        }
    }

    /**
     * Test for `getDefaultExtension()` method
     * @test
     */
    public function testGetDefaultExtension()
    {
        $this->assertEquals('sql', $this->Sqlite->getDefaultExtension());
    }

    /**
     * Test for `getExportExecutable()` method
     * @test
     */
    public function testGetExportExecutable()
    {
        $method = 'getExportExecutable';
        $sqlite3 = $this->getBinary('sqlite3');

        $expected = $sqlite3 . ' /tmp/example.sq3 .dump | ' . $this->getBinary('bzip2') . ' > backup.sql.bz2 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Sqlite, $method, ['backup.sql.bz2']));

        $expected = $sqlite3 . ' /tmp/example.sq3 .dump | ' . $this->getBinary('gzip') . ' > backup.sql.gz 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Sqlite, $method, ['backup.sql.gz']));

        $expected = $sqlite3 . ' /tmp/example.sq3 .dump > backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Sqlite, $method, ['backup.sql']));
    }

    /**
     * Test for `getExtension()` method
     * @test
     */
    public function testGetExtension()
    {
        $extensions = [
            'backup.sql' => 'sql',
            'backup.sql.bz2' => 'sql.bz2',
            'backup.sql.gz' => 'sql.gz',
            'text.txt' => null,
        ];

        foreach ($extensions as $filename => $expectedExtension) {
            $this->assertEquals($expectedExtension, $this->Sqlite->getExtension($filename));
        }
    }

    /**
     * Test for `getImportExecutable()` method
     * @test
     */
    public function testGetImportExecutable()
    {
        $method = 'getImportExecutable';
        $sqlite3 = $this->getBinary('sqlite3');

        $expected = $this->getBinary('bzip2') . ' -dc backup.sql.bz2 | ' . $sqlite3 . ' /tmp/example.sq3 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Sqlite, $method, ['backup.sql.bz2']));

        $expected = $this->getBinary('gzip') . ' -dc backup.sql.gz | ' . $sqlite3 . ' /tmp/example.sq3 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Sqlite, $method, ['backup.sql.gz']));

        $expected = $sqlite3 . ' /tmp/example.sq3 < backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Sqlite, $method, ['backup.sql']));
    }

    /**
     * Test for `getValidExtensions()` method
     * @test
     */
    public function testGetValidExtensions()
    {
        $this->assertEquals(['sql.bz2', 'sql.gz', 'sql'], $this->Sqlite->getValidExtensions());
    }

    /**
     * Test for `getValidCompressions()` method
     * @test
     */
    public function testGetValidCompressions()
    {
        $this->assertEquals(['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false], $this->Sqlite->getValidCompressions());
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Sqlite->export($backup));
        $this->assertFileExists($backup);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage sqlite3 failed with exit code `1`
     * @test
     */
    public function testExportOnFailure()
    {
        $config = $this->getProperty($this->Sqlite, 'config');

        $this->Sqlite = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['getExportExecutable'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Sqlite->method('getExportExecutable')
             ->will($this->returnCallback(function () use ($config) {
                return sprintf('%s %s .dump noExisting 2>/dev/null', $this->getBinary('sqlite3'), $config['database']);
             }));

        $this->Sqlite->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Sqlite->export($backup);

        $this->assertTrue($this->Sqlite->import($backup));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage sqlite3 failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $config = $this->getProperty($this->Sqlite, 'config');

        $this->Sqlite = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['dropTables', 'getImportExecutable'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Sqlite->method('getImportExecutable')
             ->will($this->returnCallback(function () use ($config) {
                return sprintf('%s %s .dump noExisting 2>/dev/null', $this->getBinary('sqlite3'), $config['database']);
             }));

        $this->Sqlite->import('noExistingFile');
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @test
     */
    public function testExportAndImport()
    {
        $this->loadFixtures('Sqlite\Articles', 'Sqlite\Comments');

        $this->_testExportAndImport($this->Sqlite);
    }
}
