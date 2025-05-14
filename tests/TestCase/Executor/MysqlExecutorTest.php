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
use Override;
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
    /**
     * @var \Cake\Datasource\ConnectionInterface&\Mockery\MockInterface
     */
    protected ConnectionInterface $Connection;

    /**
     * @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface
     */
    protected MysqlExecutor $Executor;

    /**
     * @inheritDoc
     */
    #[Override]
    public function setUp(): void
    {
        parent::setUp();

        $this->Connection = Mockery::mock(ConnectionInterface::class)->shouldIgnoreMissing();

        $this->Executor = Mockery::spy(
            'DatabaseBackup\Executor\MysqlExecutor[deleteAuthFile, writeAuthFile]',
            [$this->Connection, OperationType::Export]
        );
        $this->Executor->shouldAllowMockingProtectedMethods();
        $this->Executor->makePartial();
    }

    #[Test]
    public function testAfterExport(): void
    {
        $this->Executor->dispatchEvent('Backup.afterExport');

        $this->Executor
            ->shouldHaveReceived('deleteAuthFile')
            ->once();
    }

    #[Test]
    public function testAfterImport(): void
    {
        $this->Executor->dispatchEvent('Backup.afterImport');

        $this->Executor
            ->shouldHaveReceived('deleteAuthFile')
            ->once();
    }

    #[Test]
    public function testBeforeExport(): void
    {
        $Event = $this->Executor->dispatchEvent('Backup.beforeExport');

        $this->assertTrue($Event->getResult());

        $this->Executor
            ->shouldHaveReceived('writeAuthFile')
            ->with('[mysqldump]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}')
            ->once();
    }

    #[Test]
    public function testBeforeImport(): void
    {
        $Event = $this->Executor->dispatchEvent('Backup.beforeImport');

        $this->assertTrue($Event->getResult());

        $this->Executor
            ->shouldHaveReceived('writeAuthFile')
            ->with('[client]' . PHP_EOL .
                'user={{USER}}' . PHP_EOL .
                'password="{{PASSWORD}}"' . PHP_EOL .
                'host={{HOST}}')
            ->once();
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
        $Executor = new MysqlExecutor(Connection: $this->Connection, OperationType: $OperationType);

        $this->assertSame($expectedBinaryNames, $Executor->getBinaryName());
    }

    #[Test]
    #[RunInSeparateProcess]
    public function testWriteAuthFile(): void
    {
        /** @var \Symfony\Component\Filesystem\Filesystem&\Mockery\MockInterface $Filesystem */
        $Filesystem = Mockery::mock('overload:Symfony\Component\Filesystem\Filesystem');

        $Filesystem
            ->shouldReceive('dumpFile')
            ->with('/path/to/my/auth/file', 'my-content')
            ->once();

        $Filesystem
            ->shouldReceive('exists')
            ->with('/path/to/my/auth/file')
            ->andReturnTrue()
            ->once();

        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::mock(MysqlExecutor::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $MysqlExecutor
            ->shouldReceive('getAuthFilePath')
            ->andReturn('/path/to/my/auth/file')
            ->once();

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
            ->with('/path/to/my/auth/file')
            ->once();

        /** @var \DatabaseBackup\Executor\MysqlExecutor&\Mockery\MockInterface $MysqlExecutor */
        $MysqlExecutor = Mockery::mock(MysqlExecutor::class)
            ->shouldAllowMockingProtectedMethods()
            ->makePartial();

        $MysqlExecutor
            ->shouldReceive('getAuthFilePath')
            ->andReturn('/path/to/my/auth/file')
            ->once();

        $MysqlExecutor->deleteAuthFile();
    }
}
