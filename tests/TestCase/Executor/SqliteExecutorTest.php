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

use App\Database\FakeConnection;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Event\EventInterface;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use InvalidArgumentException;
use Mockery;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * SqliteExecutorTest.
 */
#[CoversClass(SqliteExecutor::class)]
class SqliteExecutorTest extends TestCase
{
    protected SqliteExecutor $SqliteExecutor;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->SqliteExecutor = new SqliteExecutor(
            Connection: new FakeConnection(),
            OperationType: OperationType::Export,
        );
    }

    /**
     * @link \DatabaseBackup\Executor\SqliteExecutor::getBinaryName()
     */
    #[Test]
    #[TestWith(['sqlite3', OperationType::Export])]
    #[TestWith(['sqlite3', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinaryName, OperationType $OperationType): void
    {
        $SqliteExecutor = new SqliteExecutor(Connection: new FakeConnection(), OperationType: $OperationType);

        $this->assertSame($expectedBinaryName, $SqliteExecutor->getBinaryName());
    }

    /**
     * @link \DatabaseBackup\Executor\SqliteExecutor::getAllTableSchemas()
     */
    #[Test]
    public function testGetAllTableSchemas(): void
    {
        $result = $this->SqliteExecutor->getAllTableSchemas();

        $this->assertCount(2, $result);
        $this->assertSame('articles', $result[0]->name());
        $this->assertSame('comments', $result[1]->name());
    }

    /**
     * @link \DatabaseBackup\Executor\SqliteExecutor::dropAllTables()
     */
    #[Test]
    public function testDropAllTables(): void
    {
        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(FakeConnection::class . '[execute]');

        $Connection
            ->shouldReceive('execute')
            ->with('DROP TABLE "articles"')
            ->once();

        $Connection
            ->shouldReceive('execute')
            ->with('DROP TABLE "comments"')
            ->once();

        $SqliteExecutor = new SqliteExecutor(Connection: $Connection, OperationType: OperationType::Export);
        $SqliteExecutor->dropAllTables();
    }

    /**
     * @link \DatabaseBackup\Executor\SqliteExecutor::beforeImport()
     */
    #[Test]
    public function testBeforeImport(): void
    {
        //@todo Temporary, needed by the 3.0.x patch (issue #238), to be reviewed when properly fixed in 3.1.x
        Configure::write('DatabaseBackup.binaries.sqlite3', '/path/to/sqlite3');

        $Driver = Mockery::spy(Driver::class);

        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(Connection::class);
        $Connection
            ->shouldReceive('getDriver')
            ->andReturn($Driver);

        /** @var \DatabaseBackup\Executor\SqliteExecutor&\Mockery\MockInterface $SqliteExecutor */
        $SqliteExecutor = Mockery::spy(
            SqliteExecutor::class . '[dropAllTables]',
            [$Connection, OperationType::Export],
        );

        $SqliteExecutor->dispatchEvent('Backup.beforeImport');

        $SqliteExecutor
            ->shouldHaveReceived('dropAllTables')
            ->once();

        $Driver
            ->shouldHaveReceived('connect')
            ->once();

        $Driver
            ->shouldHaveReceived('disconnect')
            ->once();
    }

    /**
     * If the `sqlite3` binary cannot be found, the tables are not dropped.
     *
     * @link \DatabaseBackup\Executor\SqliteExecutor::beforeImport()
     * @todo Temporary test for the 3.0.x patch (issue #238), to be reviewed or removed when properly fixed in 3.1.x
     */
    #[Test]
    public function testBeforeImportWithBinaryNotFound(): void
    {
        Configure::write('DatabaseBackup.binaries.sqlite3', '');

        $SqliteExecutor = new class (new FakeConnection(), OperationType::Import) extends SqliteExecutor {
            public function dropAllTables(): array
            {
                Assert::fail('The tables must not be dropped');
            }
        };

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Binary for `sqlite3` not found');
        $SqliteExecutor->dispatchEvent('Backup.beforeImport');
    }

    /**
     * If another listener stops the `Backup.beforeImport` event, the tables are not dropped.
     *
     * @link \DatabaseBackup\Executor\SqliteExecutor::beforeImport()
     */
    #[Test]
    public function testBeforeImportStoppedByAnotherListener(): void
    {
        $SqliteExecutor = new class (new FakeConnection(), OperationType::Import) extends SqliteExecutor {
            public function dropAllTables(): array
            {
                Assert::fail('The tables must not be dropped');
            }
        };

        $SqliteExecutor->getEventManager()->on(
            'Backup.beforeImport',
            static function (EventInterface $Event): void {
                $Event->stopPropagation();
            },
        );

        $Event = $SqliteExecutor->dispatchEvent('Backup.beforeImport');

        $this->assertTrue($Event->isStopped());
    }
}
