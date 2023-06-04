<?php
/** @noinspection PhpDocMissingThrowsInspection, PhpUnhandledExceptionInspection */
declare(strict_types=1);

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

use DatabaseBackup\Driver\Driver;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;
use Tools\Filesystem;

/**
 * MysqlTest class
 */
class MysqlTest extends DriverTestCase
{
    /**
     * @var \DatabaseBackup\Driver\Mysql&\PHPUnit\Framework\MockObject\MockObject
     */
    protected Driver $Driver;

    /**
     * Called before every test method
     * @return void
     */
    public function setUp(): void
    {
        $connection = $this->getConnection('test');

        if (get_class_short_name($connection->getDriver()) !== 'Mysql') {
            $this->markTestSkipped('Skipping tests for Mysql, current driver is ' . $this->Driver->getDriverName());
        }

        $this->Driver ??= $this->getMockBuilder(Mysql::class)
            ->setConstructorArgs([$this->getConnection('test')])
            ->onlyMethods(['getAuthFilePath', 'deleteAuthFile', 'writeAuthFile'])
            ->getMock();

        parent::setUp();
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::afterExport()
     */
    public function testAfterExport(): void
    {
        $expectedAuthFile = Filesystem::createTmpFile();
        $this->assertFileExists($expectedAuthFile);

        $Driver = $this->getMockBuilder(Mysql::class)
            ->setConstructorArgs([$this->getConnection('test')])
            ->onlyMethods(['getAuthFilePath'])
            ->getMock();
        $Driver->method('getAuthFilePath')->willReturn($expectedAuthFile);
        $Driver->dispatchEvent('Backup.afterExport');
        $this->assertFileDoesNotExist($expectedAuthFile);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::afterImport()
     */
    public function testAfterImport(): void
    {
        $expectedAuthFile = Filesystem::createTmpFile();
        $this->assertFileExists($expectedAuthFile);

        $Driver = $this->getMockBuilder(Mysql::class)
            ->setConstructorArgs([$this->getConnection('test')])
            ->onlyMethods(['getAuthFilePath'])
            ->getMock();
        $Driver->method('getAuthFilePath')->willReturn($expectedAuthFile);
        $Driver->dispatchEvent('Backup.afterImport');
        $this->assertFileDoesNotExist($expectedAuthFile);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::beforeExport()
     */
    public function testBeforeExport(): void
    {
        $expectedContent = '[mysqldump]' . PHP_EOL .
            'user={{USER}}' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host={{HOST}}';

        $this->Driver->expects($this->once())
            ->method('writeAuthFile')
            ->with($this->equalTo($expectedContent))
            ->willReturn(true);

        $this->assertTrue($this->Driver->dispatchEvent('Backup.beforeExport')->getResult());
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::beforeImport()
     */
    public function testBeforeImport(): void
    {
        $expectedContent = '[client]' . PHP_EOL .
            'user={{USER}}' . PHP_EOL .
            'password="{{PASSWORD}}"' . PHP_EOL .
            'host={{HOST}}';

        $this->Driver->expects($this->once())
            ->method('writeAuthFile')
            ->with($this->equalTo($expectedContent))
            ->willReturn(true);

        $this->assertTrue($this->Driver->dispatchEvent('Backup.beforeImport')->getResult());
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::writeAuthFile()
     */
    public function testWriteAuthFile(): void
    {
        $expectedAuthFile = TMP . 'auth' . uniqid();
        $this->assertFileDoesNotExist($expectedAuthFile);

        $Driver = $this->getMockBuilder(Mysql::class)
            ->setConstructorArgs([$this->getConnection('test')])
            ->onlyMethods(['getAuthFilePath'])
            ->getMock();
        $Driver->method('getAuthFilePath')->willReturn($expectedAuthFile);

        //Dispatches an event that calls and returns `writeAuthFile()`
        $this->assertTrue($Driver->dispatchEvent('Backup.beforeExport')->getResult());
        $this->assertFileExists($expectedAuthFile);
        $this->assertSame('[mysqldump]' . PHP_EOL .
            'user=travis' . PHP_EOL .
            'password=""' . PHP_EOL .
            'host=localhost', file_get_contents($expectedAuthFile));
    }
}
