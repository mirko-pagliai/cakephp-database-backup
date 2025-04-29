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
use Cake\Datasource\ConnectionManager;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;
use Generator;
use InvalidArgumentException;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * AbstractBackupUtilityTest.
 */
#[CoversClass(AbstractBackupUtility::class)]
class AbstractBackupUtilityTest extends TestCase
{
    /**
     * @var \DatabaseBackup\Utility\AbstractBackupUtility
     */
    protected AbstractBackupUtility $Utility;

    #[Override]
    protected function setUp(): void
    {
        $this->Utility = new class (Connection: null) extends AbstractBackupUtility {
            protected OperationType $OperationType = OperationType::Export;

            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };
    }

    public static function providerTestConstruct(): Generator
    {
        yield [null];
        yield ['test'];
        yield [ConnectionManager::get('test')];
    }

    #[Test]
    #[DataProvider('providerTestConstruct')]
    public function testConstruct(ConnectionInterface|string|null $Connection): void
    {
        $Utility = new class ($Connection) extends AbstractBackupUtility {
            public ConnectionInterface $Connection;

            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };

        $Connection = $Utility->Connection;

        $this->assertSame('test', $Connection->config()['name']);
    }

    #[Test]
    public function testMagicCallMethod(): void
    {
        $this->assertInstanceOf(ConnectionInterface::class, $this->Utility->getConnection());
        $this->assertSame(0, $this->Utility->getTimeout());
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

    /**
     * @return array<array{non-empty-string, non-empty-string}>
     */
    public static function makeAbsoluteFilenameProvider(): array
    {
        $defaultTarget = Configure::readOrFail('DatabaseBackup.target');

        return [
            [$defaultTarget . 'file.txt', 'file.txt',],
            [$defaultTarget . 'file.txt', $defaultTarget . 'file.txt'],
            [TMP . 'tmp_file', TMP . 'tmp_file'],
        ];
    }

    #[Test]
    #[DataProvider('makeAbsoluteFilenameProvider')]
    public function testMakeAbsoluteFilename(string $expectedAbsolutePath, string $path): void
    {
        $result = $this->Utility->makeAbsoluteFilename($path);

        $this->assertSame($expectedAbsolutePath, $result);
    }

    #[Test]
    public function testTimeout(): void
    {
        $result = $this->Utility->timeout(60);
        $this->assertSame($this->Utility, $result);

        $this->assertSame(60, $this->Utility->getTimeout());
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
        $Connection = $this->createConfiguredMock(
            ConnectionInterface::class,
            ['getDriver' => new $driverClassname()]
        );

        $Utility = new class (Connection: $Connection) extends AbstractBackupUtility {
            protected OperationType $OperationType = OperationType::Export;

            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };

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

        $Utility = new class (Connection: $Connection) extends AbstractBackupUtility {
            protected OperationType $OperationType = OperationType::Export;

            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Executor class for the `FakeDriver` driver does not exist');
        $Utility->getExecutor();
    }
}
