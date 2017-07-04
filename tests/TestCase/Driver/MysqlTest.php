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
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');

        $expected = $this->getBinary('mysqldump') . ' --defaults-file=authFile test';
        $result = $this->invokeMethod($this->Driver, '_exportExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');

        $expected = $this->getBinary('mysql') . ' --defaults-extra-file=authFile test';
        $result = $this->invokeMethod($this->Driver, '_importExecutable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test for `afterExport()` method
     * @test
     */
    public function testAfterExport()
    {
        $this->Driver = $this->getMockBuilder(Mysql::class)
            ->setMethods(['deleteAuthFile'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->expects($this->once())
            ->method('deleteAuthFile');

        $this->Driver->afterExport();
    }

    /**
     * Test for `afterImport()` method
     * @test
     */
    public function testAfterImport()
    {
        $this->Driver = $this->getMockBuilder(Mysql::class)
            ->setMethods(['deleteAuthFile'])
            ->setConstructorArgs([$this->getConnection()])
            ->getMock();

        $this->Driver->expects($this->once())
            ->method('deleteAuthFile');

        $this->Driver->afterImport();
    }

    /**
     * Test for `beforeExport()` method
     * @test
     */
    public function testBeforeExport()
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));

        $this->Driver->beforeExport();

        $expected = '[mysqldump]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));

        unlink($auth);
    }

    /**
     * Test for `beforeImport()` method
     * @test
     */
    public function testBeforeImport()
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));

        $this->Driver->beforeImport();

        $expected = '[client]' . PHP_EOL . 'user=travis' . PHP_EOL . 'password=""' . PHP_EOL . 'host=localhost';
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));
    }

    /**
     * Test for `deleteAuthFile()` method
     * @test
     */
    public function testDeleteAuthFile()
    {
        $this->assertFalse($this->invokeMethod($this->Driver, 'deleteAuthFile'));

        //Creates auth file
        $auth = tempnam(sys_get_temp_dir(), 'auth');
        $this->setProperty($this->Driver, 'auth', $auth);

        $this->assertFileExists($auth);
        $this->assertTrue($this->invokeMethod($this->Driver, 'deleteAuthFile'));
        $this->assertFileNotExists($auth);
    }

    /**
     * Test for `export()` method on failure
     * @expectedException Cake\Network\Exception\InternalErrorException
     * @expectedExceptionMessage Failed with exit code `2`
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
     * @expectedExceptionMessage Failed with exit code `1`
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
