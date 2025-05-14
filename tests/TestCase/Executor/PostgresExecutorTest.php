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
use Cake\Database\Driver\Postgres;
use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * SqliteExecutorTest class.
 */
#[CoversClass(PostgresExecutor::class)]
#[UsesClass(AbstractExecutor::class)]
class PostgresExecutorTest extends TestCase
{
    #[TestWith(['pg_dump', OperationType::Export])]
    #[TestWith(['pg_restore', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinaryName, OperationType $OperationType): void
    {
        $Connection = Mockery::mock(Connection::class)->shouldIgnoreMissing();

        $Executor = new PostgresExecutor(
            Connection: $Connection,
            OperationType: $OperationType,
        );

        $this->assertSame($expectedBinaryName, $Executor->getBinaryName());
    }
}
