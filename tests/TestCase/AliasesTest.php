<?php
declare(strict_types=1);

namespace DatabaseBackup\Test\TestCase;

use DatabaseBackup\Executor\AbstractExecutor;
use DatabaseBackup\TestSuite\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * AliasesTest.
 */
class AliasesTest extends TestCase
{
    /**
     * Checks aliases for old `Driver` classes.
     *
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    #[Test]
    public function testAliases(): void
    {
        $Stub = $this->createStub('DatabaseBackup\Driver\AbstractDriver');
        $this->assertSame(AbstractExecutor::class, get_parent_class($Stub));
    }
}
