<?php
/**
 * This file is part of cakephp-database-backup.
 *
 * cakephp-database-backup is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * cakephp-database-backup is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with cakephp-database-backup.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author      Mirko Pagliai <mirko.pagliai@gmail.com>
 * @copyright   Copyright (c) 2016, Mirko Pagliai for Nova Atlantis Ltd
 * @license     http://www.gnu.org/licenses/agpl.txt AGPL License
 * @link        http://git.novatlantis.it Nova Atlantis Ltd
 */
namespace DatabaseBackup\Test\TestCase\Driver;

use Cake\Core\Configure;
use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Sqlite;
use DatabaseBackup\TestSuite\DriverTestCase;
use Reflection\ReflectionTrait;

/**
 * SqliteTest class
 */
class SqliteTest extends DriverTestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Driver\Sqlite
     */
    protected $Driver;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'plugin.database_backup.Sqlite/Articles',
        'plugin.database_backup.Sqlite/Comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        Configure::write(DATABASE_BACKUP . '.connection', 'test_sqlite');

        parent::setUp();

        $this->Driver = new Sqlite($this->getConnection());
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
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.bz2']));

        $expected = $sqlite3 . ' /tmp/example.sq3 .dump | ' . $this->getBinary('gzip') . ' > backup.sql.gz 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.gz']));

        $expected = $sqlite3 . ' /tmp/example.sq3 .dump > backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql']));
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
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.bz2']));

        $expected = $this->getBinary('gzip') . ' -dc backup.sql.gz | ' . $sqlite3 . ' /tmp/example.sq3 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.gz']));

        $expected = $sqlite3 . ' /tmp/example.sq3 < backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql']));
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Driver->export($backup));
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
        $config = $this->getProperty($this->Driver, 'config');

        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['getExportExecutable'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('getExportExecutable')
             ->will($this->returnCallback(function () use ($config) {
                return sprintf('%s %s .dump noExisting 2>/dev/null', $this->getBinary('sqlite3'), $config['database']);
             }));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Driver->export($backup);

        $this->assertTrue($this->Driver->import($backup));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage sqlite3 failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $config = $this->getProperty($this->Driver, 'config');

        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['dropTables', 'getImportExecutable'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('getImportExecutable')
             ->will($this->returnCallback(function () use ($config) {
                return sprintf('%s %s .dump noExisting 2>/dev/null', $this->getBinary('sqlite3'), $config['database']);
             }));

        $this->Driver->import('noExistingFile');
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @test
     */
    public function testExportAndImport()
    {
        foreach (VALID_EXTENSIONS as $extension) {
            $this->loadAllFixtures();

            $this->_testExportAndImport($this->Driver, sprintf('example.%s', $extension));
        }
    }
}
