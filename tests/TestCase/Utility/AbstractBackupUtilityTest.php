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

namespace DatabaseBackup\Test\TestCase\Utility;

use App\Database\Driver\FakeDriver;
use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;
use DatabaseBackup\Utility\BackupExport;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;

/**
 * AbstractBackupUtilityTest.
 */
#[CoversClass(AbstractBackupUtility::class)]
class AbstractBackupUtilityTest extends TestCase
{
    /**
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__call()
     */
    #[Test]
    public function testMagicCallMethod(): void
    {
        $Utility = new BackupExport();
        $this->assertIsInt($Utility->getRotate());
    }

    /**
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__call()
     */
    #[Test]
    #[TestWith(['getNoExistingProperty'])]
    #[TestWith(['noExistingMethod'])]
    public function testMagicCallMethodWithNoExistingMethod(string $noExistingMethod): void
    {
        $Utility = $this->getMockBuilder(AbstractBackupUtility::class)
            ->onlyMethods(['filename'])
            ->getMock();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method `' . $Utility::class . '::' . $noExistingMethod . '()` does not exist.');
        $Utility->{$noExistingMethod}();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::__get()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testMagicGetMethodIsDeprecated(): void
    {
        $Utility = $this->createPartialMock(AbstractBackupUtility::class, ['filename']);
        $Utility->timeout(3);

        $this->deprecated(function () use ($Utility): void {
            // @phpstan-ignore property.protected, expr.resultUnused
            $Utility->timeout;
        });
    }

    public static function makeAbsoluteFilenameProvider(): Generator
    {
        yield [
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            'file.txt',
        ];

        yield [
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
        ];

        yield [
            TMP . 'tmp_file',
            TMP . 'tmp_file',
        ];
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::makeAbsoluteFilename()
     */
    #[Test]
    #[DataProvider('makeAbsoluteFilenameProvider')]
    public function testMakeAbsoluteFilename(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->createPartialMock(AbstractBackupUtility::class, ['filename'])
            ->makeAbsoluteFilename($path);
        $this->assertSame($expectedAbsolutePath, $result);
    }

    /**
     * @param class-string $expectedExecutorClassname
     * @param class-string $driverClassname
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::getExecutor()
     */
    #[Test]
    #[TestWith([MysqlExecutor::class, Mysql::class])]
    #[TestWith([PostgresExecutor::class, Postgres::class])]
    #[TestWith([SqliteExecutor::class, Sqlite::class])]
    public function testGetExecutor(string $expectedExecutorClassname, string $driverClassname): void
    {
        $Connection = $this->createStub(ConnectionInterface::class);
        $Connection
            ->method('getDriver')
            ->willReturn(new $driverClassname());

        $Utility = $this->createPartialMock(AbstractBackupUtility::class, ['filename', 'getConnection']);
        $Utility
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($Connection);

        $result = $Utility->getExecutor();
        $this->assertInstanceOf($expectedExecutorClassname, $result);
    }

    /**
     * Tests for `getExecutor()` when the executor class for the driver does not exist.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::getExecutor()
     */
    #[Test]
    public function testGetExecutorNoExistingExecutor(): void
    {
        $Connection = $this->createStub(ConnectionInterface::class);
        $Connection
            ->method('getDriver')
            ->willReturn(new FakeDriver());

        $Utility = $this->createPartialMock(AbstractBackupUtility::class, ['filename', 'getConnection']);
        $Utility
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($Connection);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Executor class for the `FakeDriver` driver does not exist');
        $Utility->getExecutor();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::getDriver()
     */
    #[Test]
    public function testGetDriver(): void
    {
        $Utility = $this->createPartialMock(AbstractBackupUtility::class, ['filename', 'getExecutor']);
        $Utility->expects($this->once())
            ->method('getExecutor');

        $Utility->getDriver();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     * @uses \DatabaseBackup\Utility\AbstractBackupUtility::getDriver()
     */
    #[Test]
    #[WithoutErrorHandler]
    public function testGetDriverIsDeprecated(): void
    {
        $this->deprecated(function (): void {
            $this->createPartialMock(AbstractBackupUtility::class, ['filename', 'getExecutor'])
                ->getDriver();
        });
    }
}
