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
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
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

        $this->SqliteExecutor = new SqliteExecutor(Connection: new FakeConnection(), OperationType: OperationType::Export);
    }

    #[Test]
    #[TestWith(['sqlite3', OperationType::Export])]
    #[TestWith(['sqlite3', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinarName, OperationType $OperationType): void
    {
        $SqliteExecutor = new SqliteExecutor(Connection: new FakeConnection(), OperationType: $OperationType);
        $this->assertSame($expectedBinarName, $SqliteExecutor->getBinaryName());
    }

    #[Test]
    public function testGetAllTableSchemas(): void
    {
        $result = $this->SqliteExecutor->getAllTableSchemas();

        $this->assertCount(2, $result);
        $this->assertSame('articles', $result[0]->name());
        $this->assertSame('comments', $result[1]->name());
    }

    #[Test]
    public function testDropAllTables(): void
    {
        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(FakeConnection::class)->makePartial();
        $Connection->shouldReceive('execute')->with('DROP TABLE "articles"')->once();
        $Connection->shouldReceive('execute')->with('DROP TABLE "comments"')->once();

        $SqliteExecutor = new SqliteExecutor(Connection: $Connection, OperationType: OperationType::Export);
        $SqliteExecutor->dropAllTables();
    }

    #[Test]
    public function testBeforeImport(): void
    {
        $Driver = Mockery::spy(Driver::class);

        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(Connection::class);
        $Connection->shouldReceive('getDriver')->andReturn($Driver);

        /** @var \DatabaseBackup\Executor\SqliteExecutor&\Mockery\MockInterface $SqliteExecutor */
        $SqliteExecutor = Mockery::spy(SqliteExecutor::class . '[dropAllTables]', [$Connection, OperationType::Export]);
        $SqliteExecutor->dispatchEvent('Backup.beforeImport');

        $SqliteExecutor->shouldHaveReceived('dropAllTables')->once();
        $Driver->shouldHaveReceived('connect')->once();
        $Driver->shouldHaveReceived('disconnect')->once();
    }
}
