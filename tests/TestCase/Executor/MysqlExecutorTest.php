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

use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\TestSuite\DriverTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Filesystem\Filesystem;

/**
 * MysqlExecutorTest class.
 */
#[CoversClass(MysqlExecutor::class)]
class MysqlExecutorTest extends DriverTestCase
{
    /**
     * Internal method to get a mock of `MysqlExecutor`.
     *
     * @param array<int, non-empty-string> $methods Methods you want to mock
     * @return \DatabaseBackup\Executor\MysqlExecutor&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getMysqlExecutorMock(array $methods = []): MysqlExecutor
    {
        return $this->getMockBuilder(MysqlExecutor::class)
            ->setConstructorArgs([ConnectionManager::get('test')])
            ->onlyMethods($methods)
            ->getMock();
    }

    /**
     * @uses \DatabaseBackup\Executor\MysqlExecutor::afterExport()
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
     * @uses \DatabaseBackup\Executor\MysqlExecutor::afterImport()
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
     * @uses \DatabaseBackup\Executor\MysqlExecutor::beforeExport()
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
     * @uses \DatabaseBackup\Executor\MysqlExecutor::beforeImport()
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
     * @uses \DatabaseBackup\Executor\MysqlExecutor::deleteAuthFile()
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
