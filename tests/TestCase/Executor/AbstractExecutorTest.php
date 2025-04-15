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

use BadMethodCallException;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\TestSuite\TestCase;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * AbstractExecutorTest.
 */
#[CoversClass(AbstractExecutor::class)]
class AbstractExecutorTest extends TestCase
{
    /**
     * @var \Cake\Datasource\ConnectionInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected ConnectionInterface $Connection;

    /**
     * @var \DatabaseBackup\Executor\AbstractExecutor
     */
    protected AbstractExecutor $Executor;

    /**
     * {@inheritDoc}
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Override]
    protected function setUp(): void
    {
        $this->Connection = $this->createMock(ConnectionInterface::class);

        $this->Executor = $this->getMockBuilder(AbstractExecutor::class)
            ->setConstructorArgs([$this->Connection])
            ->onlyMethods(['getExportBinary', 'getImportBinary'])
            ->getMock();
    }

    /**
     * @param non-empty-string $expectedNewMethod
     * @param non-empty-string $oldMethod
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['getExportCommand', 'getExportExecutable'])]
    #[TestWith(['getImportCommand', 'getImportExecutable'])]
    #[WithoutErrorHandler]
    public function testCallMagicMethod(string $expectedNewMethod, string $oldMethod): void
    {
        $Executor = $this->createPartialMock(AbstractExecutor::class, ['getExportBinary', 'getImportBinary', $expectedNewMethod]);

        $Executor
            ->expects($this->once())
            ->method($expectedNewMethod)
            ->with($this->equalTo('filename.sql'));

        $this->deprecated(fn () => $Executor->{$oldMethod}('filename.sql'));
    }

    #[Test]
    public function testCallMagicMethodNoExistingMethod(): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method `' . $this->Executor::class . '::noExistingMethod()` does not exist');
        // @phpstan-ignore method.notFound
        $this->Executor->noExistingMethod();
    }

    #[Test]
    public function testImplementedEvents(): void
    {
        $this->assertNotEmpty($this->Executor->implementedEvents());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['\'sqlite3-binary\' my-database .dump > \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'sqlite3-binary\' my-database .dump | \'gzip-binary\' > \'filename.sql.gz\'', 'filename.sql.gz'])]
    #[TestWith(['\'sqlite3-binary\' my-database .dump | \'bzip2-binary\' > \'filename.sql.bz2\'', 'filename.sql.bz2'])]
    public function testGetExportCommand(string $expectedExportCommand, string $filename): void
    {
        $Executor = $this->getMockBuilder(AbstractExecutor::class)
            ->setConstructorArgs([$this->Connection, 'Sqlite'])
            ->onlyMethods(['findBinary', 'getConfig', 'getExportBinary', 'getImportBinary'])
            ->getMock();

        $Executor
            ->expects($this->any())
            ->method('findBinary')
            ->willReturnCallback(function (Compression|string $binaryName): string {
                return ($binaryName instanceof Compression ? strtolower($binaryName->name) : $binaryName) . '-binary';
            });

        $Executor
            ->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(fn (string $key): string => 'my-' . $key);

        $result = $Executor->getExportCommand($filename);

        $this->assertSame($expectedExportCommand, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['\'sqlite3-binary\' my-database < \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'gzip-binary\' -dc \'filename.sql.gz\' | \'sqlite3-binary\' my-database', 'filename.sql.gz'])]
    #[TestWith(['\'bzip2-binary\' -dc \'filename.sql.bz2\' | \'sqlite3-binary\' my-database', 'filename.sql.bz2'])]
    public function testGetImportCommand(string $expectedImportCommand, string $filename): void
    {
        $Executor = $this->getMockBuilder(AbstractExecutor::class)
            ->setConstructorArgs([$this->Connection, 'Sqlite'])
            ->onlyMethods(['findBinary', 'getConfig', 'getExportBinary', 'getImportBinary'])
            ->getMock();

        $Executor
            ->expects($this->any())
            ->method('findBinary')
            ->willReturnCallback(function (Compression|string $binaryName): string {
                return ($binaryName instanceof Compression ? strtolower($binaryName->name) : $binaryName) . '-binary';
            });

        $Executor
            ->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(fn (string $key): string => 'my-' . $key);

        $result = $Executor->getImportCommand($filename);

        $this->assertSame($expectedImportCommand, $result);
    }

    #[Test]
    #[TestWith(['test', 'name'])]
    #[TestWith([null, 'noExisting'])]
    public function testGetConfig(?string $expectedConfig, string $configKey): void
    {
        $this->Connection
            ->expects($this->once())
            ->method('config')
            ->willReturn(['name' => 'test']);

        $result = $this->Executor->getConfig($configKey);
        $this->assertSame($expectedConfig, $result);
    }
}
