<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase;

use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\Executor\MysqlExecutor;
use DatabaseBackup\Executor\PostgresExecutor;
use DatabaseBackup\Executor\SqliteExecutor;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * AliasesTest.
 *
 * @see config/bootstrap.php
 * @todo to be removed in version 2.15.0
 */
class AliasesTest extends TestCase
{
    /**
     * Checks aliases for old `Driver` classes.
     *
     * @param class-string $expectedClass
     * @param class-string $aliasClass
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    #[TestWith([AbstractExecutor::class, 'DatabaseBackup\Driver\AbstractDriver'])]
    #[TestWith([MysqlExecutor::class, 'DatabaseBackup\Driver\Mysql'])]
    #[TestWith([PostgresExecutor::class, 'DatabaseBackup\Driver\Postgres'])]
    #[TestWith([SqliteExecutor::class, 'DatabaseBackup\Driver\Sqlite'])]
    public function testAliases(string $expectedClass, string $aliasClass): void
    {
        $AliasInstance = $this->createStub($aliasClass);

        $this->assertInstanceOf($expectedClass, $AliasInstance);
    }
}
