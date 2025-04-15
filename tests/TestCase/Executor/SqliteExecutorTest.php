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

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Database\Schema\CollectionInterface;
use Cake\Database\Schema\TableSchema;
use Cake\Database\StatementInterface;
use Cake\Datasource\ConnectionInterface;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * SqliteExecutorTest class.
 */
#[CoversClass(SqliteExecutor::class)]
#[UsesClass(AbstractExecutor::class)]
class SqliteExecutorTest extends TestCase
{
    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testDropAllTables(): void
    {
        $tables = [1 => 'articles', 2 => 'comments'];

        /**
         * `$Schema` describes the tables.
         */
        $Schema = $this->createStub(CollectionInterface::class);
        $Schema
            ->method('listTables')
            ->willReturn($tables);
        $Schema
            ->method('describe')
            ->willReturnCallback(function (string $tableName): TableSchema {
                return $this->getMockBuilder(TableSchema::class)
                    ->setConstructorArgs([$tableName])
                    ->onlyMethods([])
                    ->getMock();
            });

        $Connection = $this->createMock(Connection::class);
        $Connection
            ->method('getDriver')
            ->willReturn(new Sqlite());
        $Connection
            ->method('getSchemaCollection')
            ->willReturn($Schema);

        /**
         * The important thing is to check the number of times and the arguments with which the `Connection::execute()`
         *  method is called.
         */
        $matcher = $this->exactly(2);
        $Connection
            ->expects($matcher)
            ->method('execute')
            ->willReturnCallback(function (string $sql) use ($matcher, $tables): StatementInterface {
                $expectedSql = sprintf('DROP TABLE "%s"', $tables[$matcher->numberOfInvocations()]);
                $this->assertSame($expectedSql, $sql);

                return $this->createStub(StatementInterface::class);
            });

        $SqliteExecutor = new SqliteExecutor($Connection);
        $SqliteExecutor->dropAllTables();
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testBeforeImport(): void
    {
        $Driver = $this->createPartialMock(Sqlite::class, ['connect', 'disconnect']);
        $Driver
            ->expects($this->once())
            ->method('connect');
        $Driver
            ->expects($this->once())
            ->method('disconnect');

        $SqliteExecutor = $this->getMockBuilder(SqliteExecutor::class)
            ->setConstructorArgs([
                $this->createConfiguredStub(ConnectionInterface::class, ['getDriver' => $Driver]),
            ])
            ->onlyMethods(['dropAllTables'])
            ->getMock();

        $SqliteExecutor
            ->expects($this->once())
            ->method('dropAllTables');

        $SqliteExecutor->dispatchEvent('Backup.beforeImport');
    }
}
