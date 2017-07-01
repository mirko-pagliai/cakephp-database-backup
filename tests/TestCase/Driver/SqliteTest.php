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
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $expected = $this->getBinary('sqlite3') . ' /tmp/example.sq3 .dump';
        $result = $this->invokeMethod($this->Driver, '_exportExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $expected = $this->getBinary('sqlite3') . ' /tmp/example.sq3';
        $result = $this->invokeMethod($this->Driver, '_importExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `beforeImport()` method
     * @test
     */
    public function testBeforeImport()
    {
        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['truncateTables'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->expects($this->once())
            ->method('truncateTables');

        $this->Driver->beforeImport();
    }

    /**
     * Test for `export()` method
     * @return void
     * @test
     */
    public function testExport()
    {
        $this->loadAllFixtures();

        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Driver->export($backup));
        $this->assertFileExists($backup);

        $content = file_get_contents($backup);

        $this->assertTextContains('CREATE TABLE IF NOT EXISTS "articles"', $content);
        $this->assertTextContains('CREATE TABLE IF NOT EXISTS "comments"', $content);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testExportOnFailure()
    {
        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['_exportExecutableWithCompression'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('_exportExecutableWithCompression')
             ->will($this->returnCallback(function () {
                $config = $this->getProperty($this->Driver, 'config');

                return sprintf('%s %s .dump noExistingDir/dump.sql 2>/dev/null', $this->getBinary('sqlite3'), $config['database']);
             }));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $this->Driver = $this->getMockBuilder(Sqlite::class)
            ->setMethods(['_importExecutableWithCompression', 'truncateTables'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->method('_importExecutableWithCompression')
             ->will($this->returnCallback(function () {
                $config = $this->getProperty($this->Driver, 'config');

                return sprintf('%s %s .dump noExisting 2>/dev/null', $this->getBinary('sqlite3'), $config['database']);
             }));

        $this->Driver->import('noExistingFile');
    }
}
