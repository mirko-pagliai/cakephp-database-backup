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

        //Deletes all backups
        foreach (glob(Configure::read(MYSQL_BACKUP . '.target') . DS . '*') as $file) {
            //@codingStandardsIgnoreLine
            @unlink($file);
        }

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

        $expected = $mysqldump . ' --defaults-file=%s %s | ' . $this->getBinary('bzip2') . ' > %s 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.bz2']));

        $expected = $mysqldump . ' --defaults-file=%s %s | ' . $this->getBinary('gzip') . ' > %s 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.gz']));

        $expected = $mysqldump . ' --defaults-file=%s %s > %s 2>/dev/null';
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

        $expected = $this->getBinary('bzip2') . ' -dc %s | ' . $mysql . ' --defaults-extra-file=%s %s 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.bz2']));

        $expected = $this->getBinary('gzip') . ' -dc %s | ' . $mysql . ' --defaults-extra-file=%s %s 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Mysql, $method, ['backup.sql.gz']));

        $expected = 'cat %s | ' . $mysql . ' --defaults-extra-file=%s %s 2>/dev/null';
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
        $filename = Configure::read(MYSQL_BACKUP . '.target') . DS . 'test.sql';
        $export = $this->Mysql->export($filename);

        $this->assertTrue($export);
        $this->assertFileExists(Configure::read(MYSQL_BACKUP . '.target') . DS . 'test.sql');
    }

    /**
     * Test for `export()` method, with a failure by mysqldump
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage mysqldump failed with exit code `2`
     * @test
     */
    public function testExportMysqldumpFailure()
    {
        //Sets a no existing database
        $connection = $this->getProperty($this->Mysql, 'connection');
        $connection['database'] = 'noExisting';
        $this->setProperty($this->Mysql, 'connection', $connection);

        $this->Mysql->export(Configure::read(MYSQL_BACKUP . '.target') . DS . 'test.sql');
    }

    /**
     * Test for `import()` method
     * @test
     */
    public function testImport()
    {
        $filename = Configure::read(MYSQL_BACKUP . '.target') . DS . 'test.sql';
        $this->Mysql->export($filename);

        $import = $this->Mysql->import($filename);
        $this->assertTrue($import);
    }

    /**
     * Test for `import()` method, with a failure by mysql
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage mysql failed with exit code `1`
     * @test
     */
    public function testImportMysqlFailure()
    {
        $filename = Configure::read(MYSQL_BACKUP . '.target') . DS . 'test.sql';
        $this->Mysql->export($filename);

        //Sets a no existing database
        $connection = $this->getProperty($this->Mysql, 'connection');
        $connection['database'] = 'noExisting';
        $this->setProperty($this->Mysql, 'connection', $connection);

        $this->Mysql->import($filename);
    }
}
