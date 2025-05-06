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
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * MysqlExecutorTest class.
 */
#[CoversClass(MysqlExecutor::class)]
#[UsesClass(AbstractExecutor::class)]
class MysqlExecutorTest extends TestCase
{
    #[Test]
    public function testAfterExport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy('DatabaseBackup\Executor\MysqlExecutor[deleteAuthFile]', [
            Mockery::spy('Connection', ConnectionInterface::class),
            OperationType::Export,
        ]);
        $MysqlExecutor->shouldAllowMockingProtectedMethods();

        $MysqlExecutor->dispatchEvent('Backup.afterExport');

        $MysqlExecutor
            ->shouldHaveReceived('deleteAuthFile')
            ->once();
    }

    #[Test]
    public function testAfterImport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy('DatabaseBackup\Executor\MysqlExecutor[deleteAuthFile]', [
            Mockery::spy('Connection', ConnectionInterface::class),
            OperationType::Export,
        ]);
        $MysqlExecutor->shouldAllowMockingProtectedMethods();

        $MysqlExecutor->dispatchEvent('Backup.afterImport');

        $MysqlExecutor
            ->shouldHaveReceived('deleteAuthFile')
            ->once();
    }

    #[Test]
    public function testBeforeExport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy('DatabaseBackup\Executor\MysqlExecutor[writeAuthFile]', [
            Mockery::spy('Connection', ConnectionInterface::class),
            OperationType::Export,
        ]);
        $MysqlExecutor->shouldAllowMockingProtectedMethods();

        $Event = $MysqlExecutor->dispatchEvent('Backup.beforeExport');
        $this->assertTrue($Event->getResult());

        $MysqlExecutor
            ->shouldHaveReceived('writeAuthFile')
            ->once()
            ->with('[mysqldump]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}');
    }

    #[Test]
    public function testBeforeImport(): void
    {
        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::spy('DatabaseBackup\Executor\MysqlExecutor[writeAuthFile]', [
            Mockery::spy('Connection', ConnectionInterface::class),
            OperationType::Export,
        ]);
        $MysqlExecutor->shouldAllowMockingProtectedMethods();

        $Event = $MysqlExecutor->dispatchEvent('Backup.beforeImport');
        $this->assertTrue($Event->getResult());

        $MysqlExecutor
            ->shouldHaveReceived('writeAuthFile')
            ->once()
            ->with('[client]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}');
    }

    /**
     * @param array<string> $expectedBinaryNames
     * @param \DatabaseBackup\OperationType $OperationType
     * @return void
     */
    #[Test]
    #[TestWith([['mariadb-dump', 'mysqldump'], OperationType::Export])]
    #[TestWith([['mariadb', 'mysql'], OperationType::Import])]
    public function testGetBinaryName(array $expectedBinaryNames, OperationType $OperationType): void
    {
        $MysqlExecutor = new MysqlExecutor(
            /** @phpstan-ignore-next-line */
            Connection: Mockery::spy('Connection', ConnectionInterface::class),
            OperationType: $OperationType
        );

        $this->assertSame($expectedBinaryNames, $MysqlExecutor->getBinaryName());
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testWriteAuthFile(): void
    {
        /** @var \Symfony\Component\Filesystem\Filesystem&\Mockery\MockInterface $Filesystem */
        $Filesystem = Mockery::mock('overload:Symfony\Component\Filesystem\Filesystem');

        $Filesystem
            ->shouldReceive('dumpFile')
            ->once()
            ->with('/path/to/my/auth/file', 'my-content');

        $Filesystem
            ->shouldReceive('exists')
            ->once()
            ->with('/path/to/my/auth/file')
            ->andReturnTrue();

        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::mock(MysqlExecutor::class)
            ->makePartial();

        $MysqlExecutor->shouldAllowMockingProtectedMethods();

        $MysqlExecutor
            ->shouldReceive('getAuthFilePath')
            ->once()
            ->andReturn('/path/to/my/auth/file');

        $MysqlExecutor
            ->shouldReceive('getConfig')
            ->times(3);

        $result = $MysqlExecutor->writeAuthFile('my-content');
        $this->assertTrue($result);
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testDeleteAuthFile(): void
    {
        /** @var \Symfony\Component\Filesystem\Filesystem&\Mockery\MockInterface $Filesystem */
        $Filesystem = Mockery::mock('overload:Symfony\Component\Filesystem\Filesystem');

        $Filesystem
            ->shouldReceive('remove')
            ->once()
            ->with('/path/to/my/auth/file');

        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::mock(MysqlExecutor::class)
            ->makePartial();

        $MysqlExecutor->shouldAllowMockingProtectedMethods();

        $MysqlExecutor
            ->shouldReceive('getAuthFilePath')
            ->once()
            ->andReturn('/path/to/my/auth/file');

        $MysqlExecutor->deleteAuthFile();
    }
}
