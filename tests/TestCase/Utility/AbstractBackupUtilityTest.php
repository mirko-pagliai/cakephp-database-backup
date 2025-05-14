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

use App\Database\FakeConnection;
use BadMethodCallException;
use Cake\Core\Configure;
use Cake\Database\Driver\Mysql;
use Cake\Database\Driver\Postgres;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use DatabaseBackup\Utility\AbstractBackupUtility;
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
        $this->Utility = new class (Connection: new FakeConnection()) extends AbstractBackupUtility {
            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };
    }

    #[Test]
    #[TestWith([null])]
    #[TestWith(['test'])]
    #[TestWith([new FakeConnection()])]
    public function testConstruct(ConnectionInterface|string|null $Connection): void
    {
        $Utility = new class ($Connection) extends AbstractBackupUtility {
            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };

        $this->assertSame('test', $Utility->Connection->config()['name']);
    }

    #[Test]
    public function testMagicCallMethod(): void
    {
        $this->assertInstanceOf(FakeConnection::class, $this->Utility->getConnection());
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
    public static function providerTestMakeAbsoluteFilename(): array
    {
        $defaultTarget = Configure::readOrFail('DatabaseBackup.target');

        return [
            [$defaultTarget . 'file.txt', 'file.txt',],
            [$defaultTarget . 'file.txt', $defaultTarget . 'file.txt'],
            [TMP . 'tmp_file', TMP . 'tmp_file'],
        ];
    }

    #[Test]
    #[DataProvider('providerTestMakeAbsoluteFilename')]
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
        $Connection = new FakeConnection(['driver' => $driverClassname]);

        $Utility = new class (Connection: $Connection) extends AbstractBackupUtility {
            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };
        $Utility->OperationType = OperationType::Export;

        $Executor = $Utility->getExecutor();

        $this->assertInstanceOf($expectedExecutorClassname, $Executor);
    }

    #[Test]
    public function testGetExecutorNoExistingExecutor(): void
    {
        $Utility = new class (Connection: new FakeConnection()) extends AbstractBackupUtility {
            public function filename(string $filename): AbstractBackupUtility
            {
                return $this;
            }
        };
        $Utility->OperationType = OperationType::Export;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Executor class for the `FakeDriver` driver does not exist');
        $Utility->getExecutor();
    }
}
