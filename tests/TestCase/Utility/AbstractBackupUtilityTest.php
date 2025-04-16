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
use InvalidArgumentException;
use Override;
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
     * @var \DatabaseBackup\Utility\AbstractBackupUtility&\PHPUnit\Framework\MockObject\MockObject
     */
    protected AbstractBackupUtility $Utility;

    /**
     * @param list<non-empty-string> $methods Methods you want to mock
     * @param \Cake\Datasource\ConnectionInterface|null $Connection
     * @return \DatabaseBackup\Utility\AbstractBackupUtility&\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getBackupExportMock(array $methods = [], ?ConnectionInterface $Connection = null): AbstractBackupUtility
    {
        return $this->getMockBuilder(AbstractBackupUtility::class)
            ->setConstructorArgs([$Connection])
            ->onlyMethods(array_merge(['filename'], $methods))
            ->getMock();
    }

    #[Override]
    protected function setUp(): void
    {
        $this->Utility = $this->getBackupExportMock();
    }

    #[Test]
    public function testMagicCallMethod(): void
    {
        $this->assertIsInt($this->Utility->getTimeout());
    }

    #[Test]
    #[TestWith(['getNoExistingProperty'])]
    #[TestWith(['noExistingMethod'])]
    public function testMagicCallMethodWithNoExistingMethod(string $noExistingMethod): void
    {
        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('Method `' . $this->Utility::class . '::' . $noExistingMethod . '()` does not exist.');
        $this->Utility->{$noExistingMethod}();
    }

    #[Test]
    #[WithoutErrorHandler]
    public function testMagicGetMethod(): void
    {
        $this->Utility->timeout(3);

        // @phpstan-ignore property.protected
        $this->deprecated(fn () => $this->Utility->timeout);
    }

    #[Test]
    public function testMagicGetMethodNoExistingProperty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Undefined property: ' . $this->Utility::class . '::$noExistingProperty');
        // @phpstan-ignore property.notFound,expr.resultUnused
        $this->Utility->noExistingProperty;
    }

    /**
     * @return array<array{non-empty-string, non-empty-string}>
     */
    public static function makeAbsoluteFilenameProvider(): array
    {
        return [
            [
                Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
                'file.txt',
            ],
            [
                Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
                Configure::readOrFail('DatabaseBackup.target') . 'file.txt',
            ],
            [
                TMP . 'tmp_file',
                TMP . 'tmp_file',
            ],
        ];
    }

    #[Test]
    #[DataProvider('makeAbsoluteFilenameProvider')]
    public function testMakeAbsoluteFilename(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->Utility->makeAbsoluteFilename($path);

        $this->assertSame($expectedAbsolutePath, $result);
    }

    /**
     * @param class-string $expectedExecutorClassname
     * @param class-string $driverClassname
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith([MysqlExecutor::class, Mysql::class])]
    #[TestWith([PostgresExecutor::class, Postgres::class])]
    #[TestWith([SqliteExecutor::class, Sqlite::class])]
    public function testGetExecutor(string $expectedExecutorClassname, string $driverClassname): void
    {
        $Connection = $this->createConfiguredMock(ConnectionInterface::class, ['getDriver' => new $driverClassname()]);

        $Utility = $this->getBackupExportMock(Connection: $Connection);

        $Executor = $Utility->getExecutor();

        $this->assertInstanceOf($expectedExecutorClassname, $Executor);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testGetExecutorNoExistingExecutor(): void
    {
        $Connection = $this->createConfiguredMock(ConnectionInterface::class, ['getDriver' => new FakeDriver()]);

        $Utility = $this->getBackupExportMock(Connection: $Connection);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Executor class for the `FakeDriver` driver does not exist');
        $Utility->getExecutor();
    }

    #[Test]
    #[WithoutErrorHandler]
    public function testGetDriver(): void
    {
        $Utility = $this->getBackupExportMock(methods: ['getExecutor']);

        $Utility
            ->expects($this->once())
            ->method('getExecutor');

        $this->deprecated(fn () => $Utility->getDriver());
    }
}
