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
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

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
     * @var \DatabaseBackup\Executor\AbstractExecutor&\PHPUnit\Framework\MockObject\MockObject
     */
    protected AbstractExecutor $Executor;

    /**
     * @inheritDoc
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

    #[Test]
    public function testImplementedEvents(): void
    {
        $this->assertNotEmpty($this->Executor->implementedEvents());
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['exportExecutable > \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['exportExecutable | \'compressionBinary\' > \'filename.sql.gz\'', 'filename.sql.gz'])]
    #[TestWith(['exportExecutable | \'compressionBinary\' > \'filename.sql.bz2\'', 'filename.sql.bz2'])]
    public function testGetExportExecutable(string $expectedExportExecutable, string $filename): void
    {
        $Executor = $this->createPartialMock(AbstractExecutor::class, ['getExecutable', 'getBinary']);

        $Executor
            ->expects($this->once())
            ->method('getExecutable')
            ->willReturn('exportExecutable');

        $Executor
            ->expects($this->any())
            ->method('getBinary')
            ->willReturn('compressionBinary');

        $result = $Executor->getExportExecutable($filename);

        $this->assertSame($expectedExportExecutable, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['importExecutable < \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'compressionBinary\' -dc \'filename.sql.gz\' | importExecutable', 'filename.sql.gz'])]
    #[TestWith(['\'compressionBinary\' -dc \'filename.sql.bz2\' | importExecutable', 'filename.sql.bz2'])]
    public function testGetImportExecutable(string $expectedImportExecutable, string $filename): void
    {
        $Executor = $this->createPartialMock(AbstractExecutor::class, ['getExecutable', 'getBinary']);

        $Executor
            ->expects($this->once())
            ->method('getExecutable')
            ->willReturn('importExecutable');

        $Executor
            ->expects($this->any())
            ->method('getBinary')
            ->willReturn('compressionBinary');

        $result = $Executor->getImportExecutable($filename);

        $this->assertSame($expectedImportExecutable, $result);
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
