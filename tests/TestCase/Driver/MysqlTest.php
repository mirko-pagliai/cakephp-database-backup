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
namespace MysqlBackup\Test\TestCase\Driver;

use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Mysql;
use MysqlBackup\TestSuite\DriverTestCase;
use Reflection\ReflectionTrait;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \MysqlBackup\Driver\Mysql
     */
    protected $Mysql;

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.articles',
        'core.comments',
    ];

    /**
     * Setup the test case, backup the static object values so they can be
     * restored. Specifically backs up the contents of Configure and paths in
     *  App if they have not already been backed up
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->Mysql = new Mysql($this->getConnection());
    }

    /**
     * Teardown any static object changes and restore them
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        unset($this->Mysql);
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
            $this->assertEquals($expectedCompression, $this->Mysql->getCompression($filename));
        }
    }

    /**
     * Test for `getDefaultExtension()` method
     * @test
     */
    public function testGetDefaultExtension()
    {
        $this->assertEquals('sql', $this->Mysql->getDefaultExtension());
    }

    /**
     * Test for `getExportExecutable()` method
     * @test
     */
    public function testGetExportExecutable()
    {
        $method = 'getExportExecutable';
        $mysqldump = $this->getBinary('mysqldump');

        $expected = $mysqldump . ' --defaults-file=%s test | ' . $this->getBinary('bzip2') . ' > backup.sql.bz2 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.bz2']));

        $expected = $mysqldump . ' --defaults-file=%s test | ' . $this->getBinary('gzip') . ' > backup.sql.gz 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.gz']));

        $expected = $mysqldump . ' --defaults-file=%s test > backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql']));
    }

    /**
     * Test for `getExportStoreAuth()` method
     * @test
     */
    public function testGetExportStoreAuth()
    {
        $auth = $this->invokeMethod($this->Mysql, 'getExportStoreAuth');

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[mysqldump]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
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
            $this->assertEquals($expectedExtension, $this->Mysql->getExtension($filename));
        }
    }

    /**
     * Test for `getImportExecutable()` method
     * @test
     */
    public function testGetImportExecutable()
    {
        $method = 'getImportExecutable';
        $mysql = $this->getBinary('mysql');

        $expected = $this->getBinary('bzip2') . ' -dc backup.sql.bz2 | ' . $mysql . ' --defaults-extra-file=%s test 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.bz2']));

        $expected = $this->getBinary('gzip') . ' -dc backup.sql.gz | ' . $mysql . ' --defaults-extra-file=%s test 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.gz']));

        $expected = $mysql . ' --defaults-extra-file=%s test < backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql']));
    }

    /**
     * Test for `getImportStoreAuth()` method
     * @test
     */
    public function testGetImportStoreAuth()
    {
        $auth = $this->invokeMethod($this->Mysql, 'getImportStoreAuth');

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[client]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
    }

    /**
     * Test for `getValidExtensions()` method
     * @test
     */
    public function testGetValidExtensions()
    {
        $this->assertEquals(['sql.bz2', 'sql.gz', 'sql'], $this->Mysql->getValidExtensions());
    }

    /**
     * Test for `getValidCompressions()` method
     * @test
     */
    public function testGetValidCompressions()
    {
        $this->assertEquals(['sql.bz2' => 'bzip2', 'sql.gz' => 'gzip', 'sql' => false], $this->Mysql->getValidCompressions());
    }

    /**
     * Test for `export()` method
     * @test
     */
    public function testExport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->assertTrue($this->Mysql->export($backup));
        $this->assertFileExists($backup);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage mysqldump failed with exit code `2`
     * @test
     */
    public function testExportOnFailure()
    {
        //Sets a no existing database
        $config = $this->getProperty($this->Mysql, 'config');
        $this->setProperty($this->Mysql, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Mysql->export($this->getAbsolutePath('example.sql'));
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Mysql->export($backup);

        $this->assertTrue($this->Mysql->import($backup));
    }

    /**
     * Test for `import()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage mysql failed with exit code `1`
     * @test
     */
    public function testImportOnFailure()
    {
        $backup = $this->getAbsolutePath('example.sql');

        $this->Mysql->export($backup);

        //Sets a no existing database
        $config = $this->getProperty($this->Mysql, 'config');
        $this->setProperty($this->Mysql, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Mysql->import($backup);
    }

    /**
     * Test for `export()` and `import()` methods.
     *
     * It tests that the backup is properly exported and then imported.
     * @see \MysqlBackup\TestSuite\DriverTestCase::_testExportAndImport()
     * @test
     */
    public function testExportAndImport()
    {
        foreach ($this->Mysql->getValidExtensions() as $extension) {
            $this->loadFixtures('Articles', 'Comments');

            $this->_testExportAndImport($this->Mysql, sprintf('example.%s', $extension));
        }
    }
}
