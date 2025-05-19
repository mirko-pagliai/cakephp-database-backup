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
use Cake\Database\Driver;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

/**
 * SqliteExecutorTest.
 */
#[CoversClass(SqliteExecutor::class)]
class SqliteExecutorTest extends TestCase
{
    #[Test]
    public function testGetAllTableSchemas(): void
    {
        $SqliteExecutor = new SqliteExecutor();
        $SqliteExecutor->Connection = new FakeConnection();
        $result = $SqliteExecutor->getAllTableSchemas();

        $this->assertContainsOnlyInstancesOf(TableSchema::class, $result);
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

        $SqliteExecutor = new SqliteExecutor();
        $SqliteExecutor->Connection = $Connection;
        $SqliteExecutor->dropAllTables();
    }

    #[Test]
    public function testBeforeImport(): void
    {
        $Driver = Mockery::spy(Driver::class);

        $Connection = Mockery::mock(ConnectionInterface::class);
        $Connection->shouldReceive('getDriver')->andReturn($Driver);

        $SqliteExecutor = Mockery::spy(SqliteExecutor::class . '[dropAllTables]');
        $SqliteExecutor->Connection = $Connection;
        $SqliteExecutor->dispatchEvent('Backup.beforeImport');

        $SqliteExecutor->shouldHaveReceived('dropAllTables')->once();
        $Driver->shouldHaveReceived('connect')->once();
        $Driver->shouldHaveReceived('disconnect')->once();
    }
}
