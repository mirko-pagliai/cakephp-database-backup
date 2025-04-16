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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use Symfony\Component\Process\ExecutableFinder;

/**
 * AbstractExecutorTest.
 */
#[CoversClass(AbstractExecutor::class)]
class AbstractExecutorTest extends TestCase
{
    /**
     * @param list<non-empty-string> $methods Methods you want to mock
     * @param \Cake\Datasource\ConnectionInterface|null $Connection
     * @return \DatabaseBackup\Executor\AbstractExecutor&\PHPUnit\Framework\MockObject\MockObject
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    protected function getAbstractExecutorMock(array $methods = [], ?ConnectionInterface $Connection = null): AbstractExecutor
    {
        $Connection = $Connection ?? $this->createMock(ConnectionInterface::class);

        return $this->getMockBuilder(AbstractExecutor::class)
            ->setConstructorArgs([$Connection, 'Sqlite'])
            ->onlyMethods(array_merge($methods, ['getExportBinary', 'getImportBinary']))
            ->getMock();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testImplementedEvents(): void
    {
        $this->assertNotEmpty($this->getAbstractExecutorMock()->implementedEvents());
    }

    /**
     * @param string $expectedBinary
     * @param array<string|\DatabaseBackup\Compression> $name
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['/usr/bin/mariadb', ['mariadb']])]
    #[TestWith(['/usr/bin/mariadb', ['mariadb', 'mysql']])]
    #[TestWith(['/usr/bin/mariadb', ['mariadb', 'noExistingSecondBinary']])]
    #[TestWith(['/usr/bin/mariadb', ['noExistingFirstBinary', 'mariadb']])]
    #[TestWith(['/usr/bin/mysql', ['mysql']])]
    #[TestWith(['/usr/bin/gzip', [Compression::Gzip]])]
    public function testFindBinary(string $expectedBinary, array $name): void
    {
        $ExecutableFinder = $this->createPartialMock(ExecutableFinder::class, ['find']);

        $ExecutableFinder
            ->expects($this->any())
            ->method('find')
            ->willReturnCallback(fn (string $name): ?string => match ($name) {
                'noExistingFirstBinary', 'noExistingSecondBinary' => null,
                default => '/usr/bin/' . $name
            });

        $Executor = $this->getAbstractExecutorMock(['getExecutableFinder']);

        $Executor
            ->expects($this->once())
            ->method('getExecutableFinder')
            ->willReturn($ExecutableFinder);

        $binary = $Executor->findBinary(...$name);
        $this->assertSame($expectedBinary, $binary);
    }

    /**
     * @param string $expectedExceptionMessage
     * @param array<string|Compression> $name
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['Binary for `none` could not be found. You have to set its path manually', [Compression::None]])]
    #[TestWith(['Binary for `noExistingBinary` could not be found. You have to set its path manually', ['noExistingBinary']])]
    #[TestWith(['Binary for `noExistingFirstBinary`, `noExistingSecondBinary` could not be found. You have to set its path manually', ['noExistingFirstBinary', 'noExistingSecondBinary']])]
    public function testFindBinaryWithNoExistingBinary(string $expectedExceptionMessage, array $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedExceptionMessage);
        $this->getAbstractExecutorMock()->findBinary(...$name);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith(['\'export-binary\' my-database .dump > \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'export-binary\' my-database .dump | \'gzip-binary\' > \'filename.sql.gz\'', 'filename.sql.gz'])]
    #[TestWith(['\'export-binary\' my-database .dump | \'bzip2-binary\' > \'filename.sql.bz2\'', 'filename.sql.bz2'])]
    public function testGetExportCommand(string $expectedExportCommand, string $filename): void
    {
        $Executor = $this->getAbstractExecutorMock(['findBinary', 'getConfig']);

        $Executor
            ->expects($this->any())
            ->method('findBinary')
            ->willReturnCallback(function (Compression|string $binaryName): string {
                if ($binaryName instanceof Compression) {
                    return strtolower($binaryName->name) . '-binary';
                }

                return 'export-binary';
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
    #[TestWith(['\'import-binary\' my-database < \'filename.sql\'', 'filename.sql'])]
    #[TestWith(['\'gzip-binary\' -dc \'filename.sql.gz\' | \'import-binary\' my-database', 'filename.sql.gz'])]
    #[TestWith(['\'bzip2-binary\' -dc \'filename.sql.bz2\' | \'import-binary\' my-database', 'filename.sql.bz2'])]
    public function testGetImportCommand(string $expectedImportCommand, string $filename): void
    {
        $Executor = $this->getAbstractExecutorMock(['findBinary', 'getConfig']);

        $Executor
            ->expects($this->any())
            ->method('findBinary')
            ->willReturnCallback(function (Compression|string $binaryName): string {
                if ($binaryName instanceof Compression) {
                    return strtolower($binaryName->name) . '-binary';
                }

                return 'import-binary';
            });

        $Executor
            ->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(fn (string $key): string => 'my-' . $key);

        $result = $Executor->getImportCommand($filename);

        $this->assertSame($expectedImportCommand, $result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
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

        $Executor = $this->getAbstractExecutorMock(Connection: $Connection);

        $result = $Executor->getConfig($configKey);
        $this->assertSame($expectedConfig, $result);
    }
}
