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
use Cake\Database\Driver\Postgres;
use DatabaseBackup\Compression;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\OperationType;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * PostgresExecutorTest.
 */
#[CoversClass(PostgresExecutor::class)]
class PostgresExecutorTest extends TestCase
{
    /**
     * @link \DatabaseBackup\Executor\PostgresExecutor::getBinaryName()
     */
    #[Test]
    #[TestWith(['pg_dump', OperationType::Export])]
    #[TestWith(['pg_restore', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinaryName, OperationType $OperationType): void
    {
        $PostgresExecutor = new PostgresExecutor(Connection: new FakeConnection(), OperationType: $OperationType);

        $this->assertSame($expectedBinaryName, $PostgresExecutor->getBinaryName());
    }

    /**
     * Tests that the command starts with the one configured for the `Postgres` driver.
     *
     * @link \DatabaseBackup\Executor\Executor::getCommand()
     */
    #[Test]
    #[TestWith(['DatabaseBackup.Postgres.export', OperationType::Export])]
    #[TestWith(['DatabaseBackup.Postgres.import', OperationType::Import])]
    public function testGetCommand(string $configKey, OperationType $OperationType): void
    {
        $PostgresExecutor = new PostgresExecutor(
            Connection: new FakeConnection(['driver' => Postgres::class]),
            OperationType: $OperationType,
        );
        /** @var non-empty-string $expectedCommand */
        $expectedCommand = Configure::readOrFail($configKey);

        $this->assertStringStartsWith($expectedCommand, $PostgresExecutor->getCommand(Compression::None));
    }
}
