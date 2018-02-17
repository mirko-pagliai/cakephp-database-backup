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

use Cake\Datasource\ConnectionManager;
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
        $this->Driver = new Mysql($this->getConnection());

        parent::setUp();
    }

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');

        $expected = sprintf('%s --defaults-file=%s test', $this->getBinary('mysqldump'), escapeshellarg('authFile'));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');

        $expected = sprintf('%s --defaults-extra-file=%s test', $this->getBinary('mysql'), escapeshellarg('authFile'));
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
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

        $expected = '[mysqldump]' . PHP_EOL .
            'user=' . ConnectionManager::config('test')['username'] . PHP_EOL .
            'password="' . ConnectionManager::config('test')['password'] . '"' . PHP_EOL .
            'host=localhost';
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

        $expected = '[client]' . PHP_EOL .
            'user=' . ConnectionManager::config('test')['username'] . PHP_EOL .
            'password="' . ConnectionManager::config('test')['password'] . '"' . PHP_EOL .
            'host=localhost';
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
