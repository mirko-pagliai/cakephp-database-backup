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
use Cake\TestSuite\TestCase;
use MysqlBackup\BackupTrait;
use MysqlBackup\Driver\Mysql;
use Reflection\ReflectionTrait;

/**
 * MysqlTest class
 */
class MysqlTest extends TestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \MysqlBackup\Driver\Mysql
     */
    protected $Mysql;

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
     * Test for constructor
     * @test
     */
    public function testConstructor()
    {
        $this->assertNotEmpty($this->getProperty($this->Mysql, 'connection'));
    }

    /**
     * Test for `getExportExecutable()` method
     * @test
     */
    public function testGetExportExecutable()
    {
        $method = 'getExportExecutable';
        $mysqldump = Configure::read(MYSQL_BACKUP . '.bin.mysqldump');
        $bzip2 = Configure::read(MYSQL_BACKUP . '.bin.bzip2');
        $gzip = Configure::read(MYSQL_BACKUP . '.bin.gzip');

        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $bzip2 . ' > %s',
            $this->invokeMethod($this->Mysql, $method, ['bzip2'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s | ' . $gzip . ' > %s',
            $this->invokeMethod($this->Mysql, $method, ['gzip'])
        );
        $this->assertEquals(
            $mysqldump . ' --defaults-file=%s %s > %s',
            $this->invokeMethod($this->Mysql, $method, [false])
        );
    }

    /**
     * Test for `getExportExecutable()` method, with the `bzip2` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `bzip2` executable not available
     * @test
     */
    public function testGetExportExecutableWithBzip2NotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.bzip2', false);

        $this->invokeMethod($this->Mysql, 'getExportExecutable', ['bzip2']);
    }

    /**
     * Test for `getExportExecutable()` method, with the `gzip` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `gzip` executable not available
     * @test
     */
    public function testGetExportExecutableWithGzipNotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.gzip', false);

        $this->invokeMethod($this->Mysql, 'getExportExecutable', ['gzip']);
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
     * Test for `getImportExecutable()` method
     * @test
     */
    public function testGetImportExecutable()
    {
        $method = 'getImportExecutable';
        $mysql = Configure::read(MYSQL_BACKUP . '.bin.mysql');
        $bzip2 = Configure::read(MYSQL_BACKUP . '.bin.bzip2');
        $gzip = Configure::read(MYSQL_BACKUP . '.bin.gzip');

        $this->assertEquals(
            $bzip2 . ' -dc %s | ' . $mysql . ' --defaults-extra-file=%s %s',
            $this->invokeMethod($this->Mysql, $method, ['bzip2'])
        );
        $this->assertEquals(
            $gzip . ' -dc %s | ' . $mysql . ' --defaults-extra-file=%s %s',
            $this->invokeMethod($this->Mysql, $method, ['gzip'])
        );
        $this->assertEquals(
            'cat %s | ' . $mysql . ' --defaults-extra-file=%s %s',
            $this->invokeMethod($this->Mysql, $method, [false])
        );
    }

    /**
     * Test for `getImportExecutable()` method, with the `bzip2` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `bzip2` executable not available
     * @test
     */
    public function testGetImportExecutableWithBzip2NotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.bzip2', false);

        $this->invokeMethod($this->Mysql, 'getImportExecutable', ['bzip2']);
    }

    /**
     * Test for `getImportExecutable()` method, with the `gzip` executable not available
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage `gzip` executable not available
     * @test
     */
    public function testGetImportExecutableWithGzipNotAvailable()
    {
        Configure::write(MYSQL_BACKUP . '.bin.gzip', false);

        $this->invokeMethod($this->Mysql, 'getImportExecutable', ['gzip']);
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
}
