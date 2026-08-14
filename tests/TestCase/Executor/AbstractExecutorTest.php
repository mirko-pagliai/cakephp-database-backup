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
    protected AbstractExecutor $Executor;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $Connection = $this->createStub(ConnectionInterface::class);

        $this->Executor = new class ($Connection) extends AbstractExecutor {
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

        $this->deprecated(fn() => $Executor->{$oldMethod}('filename.sql'));
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
    public function testGetExportCommand(string $expectedCommand, string $filename): void
    {
        // It changes the expected escaping under Windows systems
        if (DS === '\\') {
            $expectedCommand = str_replace('\'', '"', $expectedCommand);
        }

        $Connection = $this->createStub(ConnectionInterface::class);

        $Executor = new class ($Connection, 'Sqlite') extends AbstractExecutor {
            public function getBinary(string|Compression $binaryName): string
            {
                return ($binaryName instanceof Compression ? strtolower($binaryName->name) : $binaryName) . '-binary';
            }

            public function getConfig(string $key): string
            {
                return 'my-' . $key;
            }
        };

        $result = $Executor->getExportCommand($filename);

        $this->assertSame($expectedCommand, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['\'sqlite3-binary\' my-database < \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'gzip-binary\' -dc \'filename.sql.gz\' | \'sqlite3-binary\' my-database', 'filename.sql.gz'])]
    #[TestWith(['\'bzip2-binary\' -dc \'filename.sql.bz2\' | \'sqlite3-binary\' my-database', 'filename.sql.bz2'])]
    public function testGetImportCommand(string $expectedCommand, string $filename): void
    {
        // It changes the expected escaping under Windows systems
        if (DS === '\\') {
            $expectedCommand = str_replace('\'', '"', $expectedCommand);
        }

        $Connection = $this->createStub(ConnectionInterface::class);

        $Executor = new class ($Connection, 'Sqlite') extends AbstractExecutor {
            public function getBinary(string|Compression $binaryName): string
            {
                return ($binaryName instanceof Compression ? strtolower($binaryName->name) : $binaryName) . '-binary';
            }

            public function getConfig(string $key): string
            {
                return 'my-' . $key;
            }
        };

        $result = $Executor->getImportCommand($filename);

        $this->assertSame($expectedCommand, $result);
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
        $Connection = $this->createMock(ConnectionInterface::class);
        $Connection
            ->expects($this->once())
            ->method('config')
            ->willReturn(['name' => 'test']);

        $Executor = new class ($Connection) extends AbstractExecutor {
        };

        $result = $Executor->getConfig($configKey);
        $this->assertSame($expectedConfig, $result);
    }
}
