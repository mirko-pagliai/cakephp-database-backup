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

namespace DatabaseBackup\Test\TestCase\Executor;

use Cake\Datasource\ConnectionInterface;
use Cake\TestSuite\TestCase;
use DatabaseBackup\Executor\MysqlExecutor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MysqlExecutorTest class.
 */
#[CoversClass(MysqlExecutor::class)]
class MysqlExecutorTest extends TestCase
{
    /**
     * @param list<non-empty-string> $methods Methods you want to mock
     * @return \DatabaseBackup\Executor\MysqlExecutor&\PHPUnit\Framework\MockObject\MockObject
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function getMysqlExecutorMock(array $methods = []): MysqlExecutor
    {
        $Connection = $this->createStub(ConnectionInterface::class);

        return $this->getMockBuilder(MysqlExecutor::class)
            ->setConstructorArgs([$Connection])
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testAfterExport(): void
    {
        $MysqlExecutor = $this->getMysqlExecutorMock(['deleteAuthFile']);
        $MysqlExecutor->expects($this->once())
            ->method('deleteAuthFile');

        $MysqlExecutor->dispatchEvent('Backup.afterExport');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testAfterImport(): void
    {
        $MysqlExecutor = $this->getMysqlExecutorMock(['deleteAuthFile']);
        $MysqlExecutor->expects($this->once())
            ->method('deleteAuthFile');

        $MysqlExecutor->dispatchEvent('Backup.afterImport');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testBeforeExport(): void
    {
        $MysqlExecutor = $this->getMysqlExecutorMock(['writeAuthFile']);
        $MysqlExecutor->expects($this->once())
            ->method('writeAuthFile')
            ->with('[mysqldump]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}');

        $MysqlExecutor->dispatchEvent('Backup.beforeExport');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testBeforeImport(): void
    {
        $MysqlExecutor = $this->getMysqlExecutorMock(['writeAuthFile']);
        $MysqlExecutor->expects($this->once())
            ->method('writeAuthFile')
            ->with('[client]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}');

        $MysqlExecutor->dispatchEvent('Backup.beforeImport');
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testDeleteAuthFile(): void
    {
        $expectedAuthFile = TMP . 'myAuthFile';

        $Filesystem = $this->createPartialMock(Filesystem::class, ['remove']);

        $Filesystem->expects($this->once())
            ->method('remove')
            ->with($expectedAuthFile);

        $MysqlExecutor = $this->getMysqlExecutorMock(['getFilesystem', 'getAuthFilePath']);
        $MysqlExecutor
            ->method('getFilesystem')
            ->willReturn($Filesystem);
        $MysqlExecutor
            ->method('getAuthFilePath')
            ->willReturn($expectedAuthFile);

        //Dispatches an event (any) that we are sure will call and return the `deleteAuthFile()` method.
        $result = $MysqlExecutor->dispatchEvent('Backup.afterExport');

        $this->assertNull($result->getResult());
    }
}
