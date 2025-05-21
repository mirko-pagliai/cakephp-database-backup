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
     protected PostgresExecutor $PostgresExecutor;

     /**
      * @inheritDoc
      */
     public function setUp(): void
     {
         parent::setUp();

         $this->PostgresExecutor = new PostgresExecutor(OperationType: OperationType::Export);
     }

    #[Test]
    #[TestWith(['pg_dump', OperationType::Export])]
    #[TestWith(['pg_restore', OperationType::Import])]
    public function testGetBinaryName(string $expectedBinarName, OperationType $OperationType): void
    {
        $this->PostgresExecutor->OperationType = $OperationType;
        $this->assertSame($expectedBinarName, $this->PostgresExecutor->getBinaryName());
    }
}
