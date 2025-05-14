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
use Cake\Database\Connection;
use Cake\Database\Driver;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\CollectionInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * SqliteExecutorTest class.
 */
#[CoversClass(SqliteExecutor::class)]
#[UsesClass(AbstractExecutor::class)]
class SqliteExecutorTest extends TestCase
{
    #[TestWith(['sqlite3', OperationType::Export])]
    #[TestWith(['sqlite3', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinaryName, OperationType $OperationType): void
    {
        $Executor = new SqliteExecutor(Connection: new FakeConnection(), OperationType: $OperationType);

        $this->assertSame($expectedBinaryName, $Executor->getBinaryName());
    }

    #[Test]
    public function testDropAllTables(): void
    {
        $Schema = new class implements CollectionInterface {
            public function listTables(): array
            {
                return [1 => 'articles', 2 => 'comments'];
            }

            public function describe(string $name, array $options = []): TableSchemaInterface
            {
                return new TableSchema($name);
            }
        };

        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(Connection::class, [['driver' => Sqlite::class]])->makePartial();
        $Connection->setSchemaCollection($Schema);

        $Connection->shouldReceive('execute')
            ->with('DROP TABLE "articles"')
            ->once();

        $Connection->shouldReceive('execute')
            ->with('DROP TABLE "comments"')
            ->once();

        $SqliteExecutor = new SqliteExecutor(Connection: $Connection, OperationType: OperationType::Export);
        $SqliteExecutor->dropAllTables();
    }

    #[Test]
    public function testBeforeImport(): void
    {
        /** @var \Cake\Database\Driver&\Mockery\MockInterface $Driver */
        $Driver = Mockery::spy(Sqlite::class);

        $Connection = new class ($Driver) extends Connection {
            public function __construct(protected Driver $Driver)
            {
            }

            public function getDriver(string $role = self::ROLE_WRITE): Driver
            {
                return $this->Driver;
            }
        };

        /** @var \DatabaseBackup\Executor\SqliteExecutor&\Mockery\MockInterface $SqliteExecutor */
        $SqliteExecutor = Mockery::spy(SqliteExecutor::class . '[dropAllTables]', [$Connection, OperationType::Export]);

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
}
