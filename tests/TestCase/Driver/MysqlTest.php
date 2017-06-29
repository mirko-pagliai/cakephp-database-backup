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

use DatabaseBackup\BackupTrait;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;
use Reflection\ReflectionTrait;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    use BackupTrait;
    use ReflectionTrait;

    /**
     * @var \DatabaseBackup\Driver\Mysql
     */
    protected $Driver;

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

        $this->Driver = new Mysql($this->getConnection());
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
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.bz2']));

        $expected = $mysqldump . ' --defaults-file=%s test | ' . $this->getBinary('gzip') . ' > backup.sql.gz 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.gz']));

        $expected = $mysqldump . ' --defaults-file=%s test > backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql']));
    }

    /**
     * Test for `getExportStoreAuth()` method
     * @test
     */
    public function testGetExportStoreAuth()
    {
        $auth = $this->invokeMethod($this->Driver, 'getExportStoreAuth');

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
        $mysql = $this->getBinary('mysql');

        $expected = $this->getBinary('bzip2') . ' -dc backup.sql.bz2 | ' . $mysql . ' --defaults-extra-file=%s test 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.bz2']));

        $expected = $this->getBinary('gzip') . ' -dc backup.sql.gz | ' . $mysql . ' --defaults-extra-file=%s test 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql.gz']));

        $expected = $mysql . ' --defaults-extra-file=%s test < backup.sql 2>/dev/null';
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, $method, ['backup.sql']));
    }

    /**
     * Test for `getImportStoreAuth()` method
     * @test
     */
    public function testGetImportStoreAuth()
    {
        $auth = $this->invokeMethod($this->Driver, 'getImportStoreAuth');

        $this->assertFileExists($auth);

        $result = file_get_contents($auth);
        $expected = '[client]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $this->assertEquals($expected, $result);

        unlink($auth);
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
        $config = $this->getProperty($this->Driver, 'config');
        $this->setProperty($this->Driver, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Driver->export($this->getAbsolutePath('example.sql'));
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

        $this->Driver->export($backup);

        //Sets a no existing database
        $config = $this->getProperty($this->Driver, 'config');
        $this->setProperty($this->Driver, 'config', array_merge($config, ['database' => 'noExisting']));

        $this->Driver->import($backup);
    }
}
