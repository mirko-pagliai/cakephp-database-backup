<?php
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

use DatabaseBackup\Driver\AbstractDriver;
use DatabaseBackup\Driver\Mysql;
use DatabaseBackup\TestSuite\DriverTestCase;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MysqlTest class.
 *
 * @uses \DatabaseBackup\Driver\Mysql
 */
class MysqlTest extends DriverTestCase
{
    /**
     * @var \DatabaseBackup\Driver\Mysql&\PHPUnit\Framework\MockObject\MockObject
     */
    protected AbstractDriver $Driver;

    /**
     * {@inheritDoc}
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function setUp(): void
    {
        if ($this->getConnection()->config()['scheme'] !== 'mysql') {
            $this->markTestSkipped('Skipping tests for mysql, current driver is `' . $this->getConnection()->config()['scheme'] . '`');
        }

        parent::setUp();

        $this->Driver = $this->createPartialMock(Mysql::class, ['getAuthFilePath', 'writeAuthFile']);
        $this->Driver->getEventManager()->on($this->Driver);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::afterExport()
     */
    public function testAfterExport(): void
    {
        $expectedAuthFile = tempnam(TMP, 'tmp');
        $Filesystem = new Filesystem();
        $Filesystem->dumpFile($expectedAuthFile, '');
        $this->assertFileExists($expectedAuthFile);

        $Driver = $this->createPartialMock(Mysql::class, ['getAuthFilePath']);
        $Driver->method('getAuthFilePath')->willReturn($expectedAuthFile);
        $Driver->getEventManager()->on($Driver);
        $Driver->dispatchEvent('Backup.afterExport');
        $this->assertFileDoesNotExist($expectedAuthFile);
    }

    /**
     * @test
     * @uses \DatabaseBackup\Driver\Mysql::afterImport()
     */
    public function testAfterImport(): void
    {
        $expectedAuthFile = tempnam(TMP, 'tmp');
        $Filesystem = new Filesystem();
        $Filesystem->dumpFile($expectedAuthFile, '');
        $this->assertFileExists($expectedAuthFile);

        $Driver = $this->createPartialMock(Mysql::class, ['getAuthFilePath']);
        $Driver->getEventManager()->on($Driver);
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

        $Driver = $this->createPartialMock(Mysql::class, ['getAuthFilePath']);
        $Driver->method('getAuthFilePath')->willReturn($expectedAuthFile);
        $Driver->getEventManager()->on($Driver);

        //Dispatches an event that calls and returns `writeAuthFile()`
        $this->assertTrue($Driver->dispatchEvent('Backup.beforeExport')->getResult());
        $this->assertFileExists($expectedAuthFile);
        $config = $Driver->getConnection()->config();
        $this->assertSame('[mysqldump]' . PHP_EOL .
            'user=' . $config['username'] . PHP_EOL .
            'password="' . ($config['password'] ?? '') . '"' . PHP_EOL .
            'host=' . $config['host'], file_get_contents($expectedAuthFile));
    }
}
