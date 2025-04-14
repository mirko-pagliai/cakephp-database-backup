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
use InvalidArgumentException;
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

        $this->Executor = new class ($this->Connection) extends AbstractExecutor {
        };
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
        $Executor = $this->createPartialMock(AbstractExecutor::class, [$expectedNewMethod]);

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
    #[TestWith(['exportCommand > \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['exportCommand | \'compressionBinary\' > \'filename.sql.gz\'', 'filename.sql.gz'])]
    #[TestWith(['exportCommand | \'compressionBinary\' > \'filename.sql.bz2\'', 'filename.sql.bz2'])]
    public function testGetExportCommand(string $expectedExportCommand, string $filename): void
    {
        $Executor = $this->createPartialMock(AbstractExecutor::class, ['getCommand', 'getBinary']);

        $Executor
            ->expects($this->once())
            ->method('getCommand')
            ->willReturn('exportCommand');

        $Executor
            ->expects($this->any())
            ->method('getBinary')
            ->willReturn('compressionBinary');

        $result = $Executor->getExportCommand($filename);

        $this->assertSame($expectedExportCommand, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['importCommand < \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'compressionBinary\' -dc \'filename.sql.gz\' | importCommand', 'filename.sql.gz'])]
    #[TestWith(['\'compressionBinary\' -dc \'filename.sql.bz2\' | importCommand', 'filename.sql.bz2'])]
    public function testGetImportCommand(string $expectedImportCommand, string $filename): void
    {
        $Executor = $this->createPartialMock(AbstractExecutor::class, ['getCommand', 'getBinary']);

        $Executor
            ->expects($this->once())
            ->method('getCommand')
            ->willReturn('importCommand');

        $Executor
            ->expects($this->any())
            ->method('getBinary')
            ->willReturn('compressionBinary');

        $result = $Executor->getImportCommand($filename);

        $this->assertSame($expectedImportCommand, $result);
    }

    #[Test]
    #[TestWith(['mysql'])]
    #[TestWith(['gzip'])]
    #[TestWith([Compression::Gzip])]
    #[TestWith([Compression::Bzip2])]
    public function testGetBinary(string|Compression $binaryName): void
    {
        $this->assertNotEmpty($this->Executor->getBinary($binaryName));
    }

    #[Test]
    #[TestWith(['noExistingBinary', 'noExistingBinary'])]
    #[TestWith(['none', Compression::None])]
    public function testGetBinaryNoExistingBinary(string $expectedBinaryName, string|Compression $binaryName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Binary for `' . $expectedBinaryName . '` could not be found. You have to set its path manually');
        $this->Executor->getBinary($binaryName);
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
