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

use App\Database\Driver\FakeDriver;
use Cake\Database\Connection;
use Cake\Database\Schema\Collection;
use Cake\Database\Schema\TableSchema;
use Cake\Database\Schema\TableSchemaInterface;
use Cake\Database\StatementInterface;
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
    public function testGetAllTableSchema(): void
    {
        /** @var \Cake\Database\Connection&\Mockery\MockInterface $Connection */
        $Connection = Mockery::mock(Connection::class, [['driver' => FakeDriver::class]])->makePartial();

        $SchemaCollection = new class ($Connection) extends Collection
        {
            public function listTables(): array
            {
                return ['articles', 'comments'];
            }

            public function describe(string $name, array $options = []): TableSchemaInterface
            {
                return new TableSchema(table: $name);
            }
        };
        $Connection->setSchemaCollection($SchemaCollection);

        $Connection->shouldReceive('execute')->with('DROP TABLE "articles"')->once();
        $Connection->shouldReceive('execute')->with('DROP TABLE "comments"')->once();

        $SqliteExecutor = new SqliteExecutor();
        $SqliteExecutor->Connection = $Connection;
        $result = $SqliteExecutor->dropAllTables();

        $this->assertCount(2, $result);
    }
}
