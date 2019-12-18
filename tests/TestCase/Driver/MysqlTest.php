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

use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    /**
     * @var \DatabaseBackup\Driver\Mysql
     */
    protected $DriverClass = Mysql::class;

    /**
     * Name of the database connection
     * @var string
     */
    protected $connection = 'test';

    /**
     * Fixtures
     * @var array
     */
    public $fixtures = [
        'core.Articles',
        'core.Comments',
    ];

    /**
     * Test for `_exportExecutable()` method
     * @test
     */
    public function testExportExecutable()
    {
        $expected = sprintf('%s --defaults-file=%s test', $this->getBinary('mysqldump'), escapeshellarg('authFile'));
        $this->setProperty($this->Driver, 'auth', 'authFile');
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_exportExecutable'));
    }

    /**
     * Test for `_exportExecutableWithCompression()` method
     * @test
     */
    public function testExportExecutableWithCompression()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');
        parent::testExportExecutableWithCompression();
    }

    /**
     * Test for `_importExecutable()` method
     * @test
     */
    public function testImportExecutable()
    {
        $expected = sprintf('%s --defaults-extra-file=%s test', $this->getBinary('mysql'), escapeshellarg('authFile'));
        $this->setProperty($this->Driver, 'auth', 'authFile');
        $this->assertEquals($expected, $this->invokeMethod($this->Driver, '_importExecutable'));
    }

    /**
     * Test for `_importExecutableWithCompression()` method
     * @test
     */
    public function testImportExecutableWithCompression()
    {
        $this->setProperty($this->Driver, 'auth', 'authFile');
        parent::testImportExecutableWithCompression();
    }

    /**
     * Test for `afterExport()` method
     * @test
     */
    public function testAfterExport()
    {
        $driver = $this->getMockForDriver(Mysql::class, ['deleteAuthFile']);
        $driver->expects($this->once())->method('deleteAuthFile');
        $this->assertNull($driver->afterExport());
    }

    /**
     * Test for `afterImport()` method
     * @test
     */
    public function testAfterImport()
    {
        $driver = $this->getMockForDriver(Mysql::class, ['deleteAuthFile']);
        $driver->expects($this->once())->method('deleteAuthFile');
        $this->assertNull($driver->afterImport());
    }

    /**
     * Test for `beforeExport()` method
     * @test
     */
    public function testBeforeExport()
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));
        $this->assertTrue($this->Driver->beforeExport());

        $expected = '[mysqldump]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
            'host=localhost';
        $auth = $this->getProperty($this->Driver, 'auth');
        $this->assertFileExists($auth);
        $this->assertEquals($expected, file_get_contents($auth));

        @unlink($auth);
    }

    /**
     * Test for `beforeImport()` method
     * @test
     */
    public function testBeforeImport()
    {
        $this->assertNull($this->getProperty($this->Driver, 'auth'));
        $this->assertTrue($this->Driver->beforeImport());

        $expected = '[client]' . PHP_EOL .
            'user=' . $this->Driver->getConfig('username') . PHP_EOL .
            'password="' . $this->Driver->getConfig('password') . '"' . PHP_EOL .
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

        $auth = tempnam(sys_get_temp_dir(), 'auth');
        $this->setProperty($this->Driver, 'auth', $auth);
        $this->assertFileExists($auth);
        $this->assertTrue($this->invokeMethod($this->Driver, 'deleteAuthFile'));
        $this->assertFileNotExists($auth);
    }
}
